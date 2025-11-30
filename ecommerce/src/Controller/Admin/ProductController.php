<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wsei\Ecommerce\Entity\Admin\Product;
use Wsei\Ecommerce\Form\Admin\ProductType;
use Wsei\Ecommerce\Repository\Admin\ProductRepository;

#[Route('/admin/product')]
#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository
    ) {
    }

    #[Route('', name: 'admin.product.index', methods: ['GET'])]
    public function index(): Response
    {
        $products = $this->productRepository->findAll();

        return $this->render('admin/pages/product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/new', name: 'admin.product.new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($product);
            $this->entityManager->flush();

            $this->addFlash('success', 'Product has been created successfully.');

            return $this->redirectToRoute('admin.product.index');
        }

        return $this->render('admin/pages/product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin.product.show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('admin/pages/product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin.product.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Product has been updated successfully.');

            return $this->redirectToRoute('admin.product.index');
        }

        return $this->render('admin/pages/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin.product.delete', methods: ['POST'])]
    public function delete(Request $request, Product $product): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($product);
            $this->entityManager->flush();

            $this->addFlash('success', 'Product has been deleted successfully.');
        }

        return $this->redirectToRoute('admin.product.index');
    }
}
