<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CartController extends AbstractController
{
    #[Route('/panier', name: 'app_cart')]
    public function index(SessionInterface $session, ProductRepository $productRepository): Response
    {


        if (!$session->get('cart', [])) {
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
        return $this->render('cart/index.html.twig', [
            'dataCart' => $dataCart,
            'total' => $total
        ]);
    }

    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add(SessionInterface $session, $id): Response
    {
        $cart = $session->get('cart');

        if (!empty($cart[$id])) {
            $cart[$id]++;
        } else {
            $cart[$id] = 1;
        }
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(SessionInterface $session, $id): Response
    {
        $cart = $session->get('cart');
        if (!empty($cart[$id])) {
            if ($cart[$id] > 1) {
                $cart[$id]--;
            } else {
                unset($cart[$id]);
            }
        }
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/delete/{id}', name: 'app_cart_delete')]
    public function delete(SessionInterface $session, $id): Response
    {
        $cart = $session->get('cart');
        if (!empty($cart[$id])) {
            unset($cart[$id]);
        }
        $session->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/cart/flush/', name: 'app_cart_flush')]
    public function flushCart(SessionInterface $session): Response
    {
        $session->set('cart', []);
        return $this->redirectToRoute('app_cart');
    }
}
