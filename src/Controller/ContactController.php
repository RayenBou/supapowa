<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {

        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $email = (new TemplatedEmail())
                ->from($formData['email'])
                ->to('contact@supapowa.fr')
                ->subject($formData['firstName'] . ' ' . $formData['lastName'] . '- Mail Site Web')
                ->text($formData['description']);
            $mailer->send($email);


            $this->addFlash('success', "Nous avons bien reçu votre e-mail ! Nous vous répondrons d'ici peu.");
            return $this->redirectToRoute('app_home');
        }



        return $this->render('contact/index.html.twig', [
            'form' => $form
        ]);
    }
}
