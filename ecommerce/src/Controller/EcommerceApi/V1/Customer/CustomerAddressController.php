<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\NotFoundException;
use Wsei\Ecommerce\EcommerceApi\Payload\AddressPayload;
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
    public function create(
        #[MapRequestPayload]
        AddressPayload $addressPayload,
        Customer $customer
    ): AddressResponse {
        $address = $addressPayload->toAddress();
        $address->setCustomer($customer);

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        return new AddressResponse($address);
    }

    #[Route('/{id}', name: 'ecommerce_api.customer.addresses.update', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload]
        AddressPayload $addressPayload,
        Customer $customer
    ): AddressResponse {
        $address = $this->addressRepository->findOneByCustomer($id, $customer->getId());

        if ($address === null) {
            throw new NotFoundException('Address not found');
        }

        $addressPayload->updateAddress($address);

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
