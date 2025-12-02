<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\Attribute\PublicAccess;
use Wsei\Ecommerce\Entity\Admin\Customer;

#[Route('/ecommerce/api/v1/customer')]
class CustomerProfileController extends AbstractController
{
    #[PublicAccess]
    #[Route('/check', name: 'ecommerce_api.customer.check', methods: ['GET'])]
    public function check(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'API is running',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/profile', name: 'ecommerce_api.customer.profile', methods: ['GET'])]
    public function profile(Customer $customer): JsonResponse
    {
        return new JsonResponse([
            'id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'firstName' => $customer->getFirstName(),
            'lastName' => $customer->getLastName(),
            'fullName' => $customer->getFullName(),
        ]);
    }

    #[Route('/addresses', name: 'ecommerce_api.customer.addresses', methods: ['GET'])]
    public function addresses(Customer $customer): JsonResponse
    {
        $addresses = $customer->getAddresses()->map(function ($address) {
            return [
                'id' => $address->getId(),
                'street' => $address->getStreet(),
                'city' => $address->getCity(),
                'postalCode' => $address->getPostalCode(),
                'country' => $address->getCountry(),
            ];
        })->toArray();

        return new JsonResponse([
            'addresses' => array_values($addresses),
        ]);
    }
}

