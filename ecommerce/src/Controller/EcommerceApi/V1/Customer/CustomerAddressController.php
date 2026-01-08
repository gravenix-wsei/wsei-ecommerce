<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\NotFoundException;
use Wsei\Ecommerce\EcommerceApi\Payload\AddressPayload;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\AddressListResponse;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\AddressResponse;
use Wsei\Ecommerce\EcommerceApi\Response\SuccessResponse;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Repository\AddressRepository;

#[Route('/ecommerce/api/v1/customer/addresses')]
#[OA\Tag(name: 'Address')]
class CustomerAddressController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AddressRepository $addressRepository,
    ) {
    }

    #[Route('', name: 'ecommerce_api.customer.addresses.index', methods: ['GET'])]
    #[OA\Get(
        path: '/customer/addresses',
        summary: 'List customer addresses',
        tags: ['Address'],
        security: [[
            'ApiToken' => [],
        ]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of addresses',
                content: new OA\JsonContent(ref: '#/components/schemas/AddressListResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Customer $customer): AddressListResponse
    {
        $addresses = $this->addressRepository->findByCustomer($customer->getId());

        return new AddressListResponse($addresses);
    }

    #[Route('', name: 'ecommerce_api.customer.addresses.create', methods: ['POST'])]
    #[OA\Post(
        path: '/customer/addresses',
        summary: 'Create new address',
        tags: ['Address'],
        security: [[
            'ApiToken' => [],
        ]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddressPayload')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Address created',
                content: new OA\JsonContent(ref: '#/components/schemas/AddressResponse')
            ),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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
    #[OA\Put(
        path: '/customer/addresses/{id}',
        summary: 'Update address',
        tags: ['Address'],
        security: [[
            'ApiToken' => [],
        ]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddressPayload')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Address updated',
                content: new OA\JsonContent(ref: '#/components/schemas/AddressResponse')
            ),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Address not found'),
        ]
    )]
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
    #[OA\Delete(
        path: '/customer/addresses/{id}',
        summary: 'Delete address',
        tags: ['Address'],
        security: [[
            'ApiToken' => [],
        ]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Address deleted',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Address not found'),
        ]
    )]
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
