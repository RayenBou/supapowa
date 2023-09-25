<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {

        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $photos = $form->get('photo')->getData();
            foreach ($photos as $photo) {
                $fichier = md5(uniqid()) . '.' . $photo->guessExtension();
                $photo->move(
                    "photo/product/",
                    $fichier
                );
                $photo = new Photo();
                $photo->setReference($fichier);
                $product->addPhoto($photo);
                $entityManager->persist($photo);
            }

            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $photos = $form->get('photo')->getData();
            if ($photos != []) {
                // Supprimer les anciennes photos associées au véhicule
                foreach ($product->getPhoto() as $photo) {
                    // supprimer les fichiers physiques liés aux photos ici s'ils sont stockés sur le serveur.

                    $nom = $photo->getReference();
                    unlink("photo/product/" . $nom);
                    $entityManager->remove($photo);
                }
                foreach ($photos as $photo) {
                    $fichier = uniqid() . '.' . $photo->guessExtension();
                    $photo->move(
                        "photo/product/",
                        $fichier
                    );
                    $nouvellePhoto = new Photo();
                    $nouvellePhoto->setReference($fichier);
                    $product->addPhoto($nouvellePhoto);
                    $entityManager->persist($nouvellePhoto);
                }
            }


            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
