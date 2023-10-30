<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Order;
use DateTimeImmutable;
use App\Entity\Command;
use Stripe\StripeClient;
use App\Form\PrepaymentType;
use App\Entity\CommandProduct;
use App\Repository\UserRepository;
use App\Repository\CommandRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommandProductRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaymentController extends AbstractController
{


    public function __construct(protected ParameterBagInterface $parameterBag)
    {
    }



    #[Route('/prepayment', name: 'app_prepayment')]
    public function reservation(Request $request, ProductRepository $productRepository, SessionInterface $session): Response
    {
        // if (!$this->getUser()) {
        //     return $this->redirectToRoute('app_login');
        // }
        if ($session->get('cart') == null) {
            return $this->redirectToRoute('app_product_index');
        }
        $total = 0;
        $dataCart = [];
        $cart = $session->get('cart', []);
        foreach ($cart as $id => $quantity) {
            $product = $productRepository->find($id);

            $dataCart[] = [
                "product" => $product,
                'quantity' => $quantity

            ];
            $total += $product->getPrice() * $quantity;
        }


        // $user = $userRepository->find($this->getUser());

        $form = $this->createForm(PrepaymentType::class, $session->get('client_data'));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            ///////////////////creation d'un tableau//////////////////////
            $client_data = [
                'total' => $total,
                'products' => $dataCart,
                'email' => $form->get('email')->getData(),
                'lastName' => $form->get('lastName')->getData(),
                'phone' => $form->get('phone')->getData(),
                'firstName' => $form->get('firstName')->getData(),
                'address' => $form->get('address')->getData(),
                'adressComplement' => $form->get('complement')->getData(),
                'city' => $form->get('city')->getData(),
                'postalCode' => $form->get('postalCode')->getData(),
                'country' => $form->get('country')->getData()
            ];



            /////////envoie du tableau en session///////////////

            $session->set('client_data', $client_data);
            // dd($client_data);
            return $this->redirectToRoute('app_payment');
        }

        return $this->render('payment/index.html.twig', [
            'form' => $form,
            'dataCart' => $dataCart,
            // 'products' => $productRepository->find($session->get('cart'))
        ]);
    }
    #[Route('/payment', name: 'app_payment')]
    public function payment(SessionInterface $session)
    {

        $client_data = $session->get('client_data');



        foreach ($client_data['products'] as $arrayItem) {


            $stripe_product[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $arrayItem['product']->getPrice() * 100,
                    'product_data' => [
                        'name' => $arrayItem['product']->getName(),
                        //'images' => [$YOUR_DOMAIN.'img/'.$produit_objet->getImage()],
                    ],
                ],
                'quantity' => $arrayItem['quantity'],
            ];
        }



        $stripe_product[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => 999,
                'product_data' => [
                    'name' => 'Frais de livraison',
                    //'images' => [$YOUR_DOMAIN.'img/'.$produit_objet->getImage()],
                ],
            ],
            'quantity' => 1,
        ];
        $stripeKey = $this->parameterBag->get('stripeSecret');
        $stripe = new StripeClient($stripeKey);
        $checkout_session = $stripe->checkout->sessions->create([
            'customer_email' => $client_data['email'],
            'line_items' => [
                $stripe_product
            ],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('app_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('app_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),

            'billing_address_collection' => 'required',

        ]);
        return $this->redirect($checkout_session->url, 303);
    }

    #[Route('/success', name: 'app_success')]
    public function Success(EntityManagerInterface $entityManager, ProductRepository $productRepository, SessionInterface $session, MailerInterface $mailer, CommandProductRepository $commandProductRepository)
    {
        $command = new Command();
        $client_data = $session->get('client_data');
        $uniqId = uniqid();

        // Création de la commande
        $command
            ->setCreatedAt(new DateTimeImmutable())
            ->setSubTotal($client_data['total'])
            ->setTotal($client_data['total'] + 9.99)
            ->setShippingAmount(9.99)
            ->setAddress($client_data['address'])
            ->setCity($client_data['city'])
            ->setPostalCode($client_data['postalCode'])
            ->setEmail($client_data['email'])
            ->setCountry($client_data['country'])
            ->setPhone($client_data['phone'])
            ->setLastName($client_data['lastName'])
            ->setFirstName($client_data['firstName'])
            ->setOrderId($uniqId);
        if ($client_data['adressComplement']) {
            $command->setAdressComplement($client_data['adressComplement']);
        }
        $entityManager->persist($command);

        foreach ($client_data['products'] as $item) {
            $commandProduct = new CommandProduct();
            $product = $productRepository->find($item['product']);
            $commandProduct
                ->setCommand($command)
                ->setProduct($product)
                ->setQuantity($item['quantity'])
                ->setIndividualPrice($product->getPrice())
                ->setTotalPrice($product->getPrice() * $item['quantity']);
            $entityManager->persist($commandProduct);
        }

        $entityManager->flush();
        // ----------------generation de pdf 

        $interProduct = $commandProductRepository->findBy(['command' => $command]);
        // dd($interProduct);
        foreach ($interProduct as $product) {

            $commandProducts[] = [
                'product' => $productRepository->find($product->getProduct()->getId()),
                'quantity' => $product->getQuantity()
            ];
        }

        // dd($commandProducts);
        $data = [
            'products' => $commandProducts,
            'id' => $command->getId(),
            'date' => $command->getCreatedAt(),
            'subTotal' => $command->getSubTotal(),
            'total' => $command->getTotal(),
            // 'imageSrc'  => $this->imageToBase64($this->getParameter('kernel.project_dir') . '/public/images/spk3.webp'),
            'firstname'         => $command->getFirstName(),
            'lastname'         => $command->getLastName(),
            'address'      => $command->getAddress(),
            'city'      => $command->getCity(),
            'postal'      => $command->getPostalCode(),
            'mobileNumber' => $command->getPhone(),
            'email'        => $command->getEmail(),
            'shippingAmount' => $command->getShippingAmount(),
        ];
        // $html =  $this->renderView('pdf/index.html.twig', $data);
        // $dompdf = new Dompdf();
        // $dompdf->loadHtml($html);
        // $dompdf->render();



        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf();
        $html =  $this->renderView('pdf/index.html.twig', $data);
        $dompdf->loadHtml($html);
        $dompdf->render();
        $output = $dompdf->output();
        file_put_contents('output.pdf', $output);
        $fileName = 'output.pdf';
        // ---------------Envoie d'email-----------------------

        $orderEmailId = $command->getOrderId();
        $email = (new TemplatedEmail())
            ->from('info@supapowa.com')
            ->to($client_data['email'])
            ->htmlTemplate('pdf/emailOrder.html.twig')
            ->subject('Order n. ' . $command->getOrderId())
            ->attach(fopen($fileName, 'r'), "commande $orderEmailId.pdf", 'application/pdf');
        // ->text('Merci pour votre commande');
        $mailer->send($email);


        $session->set('cart', null);

        return $this->redirectToRoute('app_confirmed_order', [
            'id' => $command->getOrderId(),
            'email' => $command->getEmail(),

        ]);
    }



    #[Route('/commande-confirme/{id}/{email}', name: 'app_confirmed_order')]
    public function CommandConfirmed(CommandRepository $commandRepository, $id, CommandProductRepository $commandProductRepository, ProductRepository $productRepository)
    {

        $command = $commandRepository->findOneBy(['orderId' => $id]);
        $interProduct = $commandProductRepository->findBy(['command' => $command]);

        foreach ($interProduct as $product) {

            $commandProduct[] = [
                'product' => $productRepository->find($product->getProduct()->getId()),
                'quantity' => $product->getQuantity()
            ];
        }

        return $this->render('command/confirmedOrder.html.twig', [
            'command' => $command,
            'commandProduct' => $commandProduct
        ]);
    }








    #[Route('/cancel', name: 'app_cancel')]
    public function appCancel()
    {
        return $this->render('reservation/cancel.html.twig');
    }
}
