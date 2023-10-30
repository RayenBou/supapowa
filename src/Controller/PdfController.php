<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Repository\CommandRepository;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\CommandProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PdfController extends AbstractController
{
    #[Route('/invoice/{id}', name: 'app_pdf')]
    public function index(CommandRepository $commandRepository, CommandProductRepository $commandProductRepository, ProductRepository $productRepository, int $id): Response
    {
        // return $this->render('pdf_generator/index.html.twig', [
        //     'controller_name' => 'PdfGeneratorController',
        // ]);



        $command = $commandRepository->find($id);
        $interProduct = $commandProductRepository->findBy(['command' => $command]);
        // dd($interProduct);
        foreach ($interProduct as $product) {

            $commandProduct[] = [
                'product' => $productRepository->find($product->getProduct()->getId()),
                'quantity' => $product->getQuantity()
            ];
        }


        $data = [
            'products' => $commandProduct,
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

        // return new Response(
        //     $dompdf->stream(uniqid(), ["Attachment" => false]),
        //     Response::HTTP_OK,
        //     ['Content-Type' => 'application/pdf']
        // );

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf();
        $html =  $this->renderView('pdf/index.html.twig', $data);
        $dompdf->loadHtml($html);
        $dompdf->render();
        $stream = $dompdf->stream('Commande_' . $command->getOrderId());
        return new Response(
            $stream,
            Response::HTTP_OK,
            ['Content-Type' => 'application/pdf']
        );
    }
    private function imageToBase64($path)
    {
        $path = $path;
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return $base64;
    }
}
