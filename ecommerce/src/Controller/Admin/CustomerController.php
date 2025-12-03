<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Form\Admin\CustomerType;
use Wsei\Ecommerce\Repository\CustomerRepository;

#[Route('/admin/customer')]
#[IsGranted('ROLE_ADMIN')]
class CustomerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CustomerRepository $customerRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('', name: 'admin.customer.index', methods: ['GET'])]
    public function index(): Response
    {
        $customers = $this->customerRepository->findAll();

        return $this->render('admin/pages/customer/index.html.twig', [
            'customers' => $customers,
        ]);
    }

    #[Route('/new', name: 'admin.customer.new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($customer, $customer->getPassword());
            $customer->setPassword($hashedPassword);

            $this->entityManager->persist($customer);
            $this->entityManager->flush();

            $this->addFlash('success', 'Customer has been created successfully.');

            return $this->redirectToRoute('admin.customer.index');
        }

        return $this->render('admin/pages/customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin.customer.show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        return $this->render('admin/pages/customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin.customer.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer): Response
    {
        $form = $this->createForm(CustomerType::class, $customer, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if password was provided (optional for edit)
            $newPassword = $form->get('password')
                ->getData();
            if (! empty($newPassword)) {
                $hashedPassword = $this->passwordHasher->hashPassword($customer, $newPassword);
                $customer->setPassword($hashedPassword);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Customer has been updated successfully.');

            return $this->redirectToRoute('admin.customer.index');
        }

        return $this->render('admin/pages/customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin.customer.delete', methods: ['POST'])]
    public function delete(Request $request, Customer $customer): Response
    {
        if ($this->isCsrfTokenValid('delete' . $customer->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($customer);
            $this->entityManager->flush();

            $this->addFlash('success', 'Customer has been deleted successfully.');
        }

        return $this->redirectToRoute('admin.customer.index');
    }
}
