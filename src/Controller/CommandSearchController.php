<?php

namespace App\Controller;

use App\Form\CommandType;
use App\Form\CommandSearchType;
use App\Repository\CommandRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandSearchController extends AbstractController
{
    #[Route('/ma-commande', name: 'app_command_access')]
    public function client(Request $request, CommandRepository $commandRepository): Response
    {


        $form = $this->createForm(CommandSearchType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            $email = $form->get('email')->getData();
            $orderId = $form->get('orderId')->getData();


            $command = $commandRepository->findOneBy(['email' => $email, 'orderId' => $orderId]);
            if (!$command) {

                return $this->render('command_search/error.html.twig', []);
            } else {
                return $this->render('command_search/view.html.twig', [
                    'command' => $command
                ]);
            }
        }
        return $this->render('command_search/access.html.twig', [

            'form' => $form
        ]);
    }
}
