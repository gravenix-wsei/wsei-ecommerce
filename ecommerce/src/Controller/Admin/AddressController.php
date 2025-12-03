<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wsei\Ecommerce\Entity\Address;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Form\Admin\AddressType;
use Wsei\Ecommerce\Repository\AddressRepository;

#[Route('/admin/customer/{customerId}/address')]
#[IsGranted('ROLE_ADMIN')]
class AddressController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressRepository $addressRepository
    ) {
    }

    #[Route('/new', name: 'admin.address.new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $customerId): Response
    {
        $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);

        if (! $customer) {
            throw $this->createNotFoundException('Customer not found');
        }

        $address = new Address();
        $address->setCustomer($customer);

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($address);
            $this->entityManager->flush();

            $this->addFlash('success', 'Address has been created successfully.');

            return $this->redirectToRoute('admin.customer.show', [
                'id' => $customerId,
            ]);
        }

        return $this->render('admin/pages/customer/address/new.html.twig', [
            'customer' => $customer,
            'address' => $address,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin.address.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $customerId, int $id): Response
    {
        $address = $this->getCustomerAddressOrThrowException($id, $customerId);

        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Address has been updated successfully.');

            return $this->redirectToRoute('admin.customer.show', [
                'id' => $customerId,
            ]);
        }

        return $this->render('admin/pages/customer/address/edit.html.twig', [
            'customer' => $address->getCustomer(),
            'address' => $address,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin.address.delete', methods: ['POST'])]
    public function delete(Request $request, int $customerId, int $id): Response
    {
        $address = $this->getCustomerAddressOrThrowException($id, $customerId);

        if ($this->isCsrfTokenValid('delete' . $address->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($address);
            $this->entityManager->flush();

            $this->addFlash('success', 'Address has been deleted successfully.');
        }

        return $this->redirectToRoute('admin.customer.show', [
            'id' => $customerId,
        ]);
    }

    private function getCustomerAddressOrThrowException(int $id, int $customerId): Address
    {
        $address = $this->addressRepository->findOneByCustomer($id, $customerId);

        if (! $address) {
            throw $this->createNotFoundException('Address not found for this customer');
        }
        return $address;
    }
}
