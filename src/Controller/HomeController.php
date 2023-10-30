<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        if ($this->getUser()) {
            if (!$this->getUser()->isActivated()) {
                return $this->redirectToRoute('app_logout');
            }
            //  else {
            //     return $this->redirectToRoute('app_dashboard');
            // }
        }



        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
    #[Route('/aboutus', name: 'app_aboutus')]
    public function aboutUs(): Response
    {
        return $this->render('home/aboutus.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
