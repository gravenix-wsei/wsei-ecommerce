<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\BadRequestException;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\NotFoundException;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\AddressResponse;
use Wsei\Ecommerce\EcommerceApi\Response\SuccessResponse;
use Wsei\Ecommerce\Entity\Address;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Repository\AddressRepository;

#[Route('/ecommerce/api/v1/customer/addresses')]
class CustomerAddressController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AddressRepository $addressRepository,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'ecommerce_api.customer.addresses.index', methods: ['GET'])]
    public function index(Customer $customer): JsonResponse
    {
        $addresses = $this->addressRepository->findByCustomer($customer->getId());

        $data = array_map(function (Address $address) {
            return [
                'id' => $address->getId(),
                'firstName' => $address->getFirstName(),
                'lastName' => $address->getLastName(),
                'street' => $address->getStreet(),
                'zipcode' => $address->getZipcode(),
                'city' => $address->getCity(),
                'country' => $address->getCountry(),
            ];
        }, $addresses);

        return new JsonResponse([
            'addresses' => $data,
            'apiDescription' => 'AddressList',
        ]);
    }

    #[Route('', name: 'ecommerce_api.customer.addresses.create', methods: ['POST'])]
    public function create(Request $request, Customer $customer): AddressResponse
    {
        $data = json_decode($request->getContent(), true);

        if (! isset($data['firstName'], $data['lastName'], $data['street'], $data['zipcode'], $data['city'], $data['country'])) {
            throw new BadRequestException(
                'All fields are required: firstName, lastName, street, zipcode, city, country'
            );
        }

        $address = new Address();
        $address->setFirstName($data['firstName']);
        $address->setLastName($data['lastName']);
        $address->setStreet($data['street']);
        $address->setZipcode($data['zipcode']);
        $address->setCity($data['city']);
        $address->setCountry($data['country']);
        $address->setCustomer($customer);

        $errors = $this->validator->validate($address);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new BadRequestException(implode(', ', $errorMessages));
        }

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        return new AddressResponse($address);
    }

    #[Route('/{id}', name: 'ecommerce_api.customer.addresses.update', methods: ['PUT'])]
    public function update(int $id, Request $request, Customer $customer): AddressResponse
    {
        $address = $this->addressRepository->findOneByCustomer($id, $customer->getId());

        if ($address === null) {
            throw new NotFoundException('Address not found');
        }

        $data = json_decode($request->getContent(), true);

        if (! isset($data['firstName'], $data['lastName'], $data['street'], $data['zipcode'], $data['city'], $data['country'])) {
            throw new BadRequestException(
                'All fields are required: firstName, lastName, street, zipcode, city, country'
            );
        }

        $address->setFirstName($data['firstName']);
        $address->setLastName($data['lastName']);
        $address->setStreet($data['street']);
        $address->setZipcode($data['zipcode']);
        $address->setCity($data['city']);
        $address->setCountry($data['country']);

        $errors = $this->validator->validate($address);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new BadRequestException(implode(', ', $errorMessages));
        }

        $this->entityManager->flush();

        return new AddressResponse($address);
    }

    #[Route('/{id}', name: 'ecommerce_api.customer.addresses.delete', methods: ['DELETE'])]
    public function delete(int $id, Customer $customer): Response
    {
        $address = $this->addressRepository->findOneByCustomer($id, $customer->getId());

        if ($address === null) {
            throw new NotFoundException('Address not found');
        }

        $this->entityManager->remove($address);
        $this->entityManager->flush();

        return new SuccessResponse();
    }
}
