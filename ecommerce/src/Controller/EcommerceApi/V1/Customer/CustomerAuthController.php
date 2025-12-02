<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\BadRequestException;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\InvalidCredentialsException;
use Wsei\Ecommerce\Entity\Admin\ApiToken;
use Wsei\Ecommerce\Entity\Admin\Customer as CustomerEntity;
use Wsei\Ecommerce\Repository\Admin\CustomerRepository;
use Wsei\Ecommerce\Utility\ApiTokenHelper;

#[Route('/ecommerce/api/v1/customer')]
class CustomerAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomerRepository $customerRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[PublicAccess]
    #[Route('/login', name: 'ecommerce_api.customer.login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (! isset($data['email']) || ! isset($data['password'])) {
            throw new BadRequestException('Email and password are required');
        }

        $customer = $this->customerRepository->findOneBy(['email' => $data['email']]);

        if ($customer === null || ! $this->passwordHasher->isPasswordValid($customer, $data['password'])) {
            throw new InvalidCredentialsException();
        }

        // Check if customer already has a token
        $apiToken = $customer->getApiToken();

        if ($apiToken !== null) {
            // Extend existing token expiration
            $apiToken->extendExpiration();
        } else {
            // Create new token
            $apiToken = new ApiToken();
            $apiToken->setToken(ApiTokenHelper::generate());
            $apiToken->setCustomer($customer);
            $this->entityManager->persist($apiToken);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'token' => $apiToken->getToken(),
            'expiresAt' => $apiToken->getExpiresAt()->format('Y-m-d H:i:s'),
        ], Response::HTTP_OK);
    }

    #[Route('/logout', name: 'ecommerce_api.customer.logout', methods: ['POST'])]
    public function logout(CustomerEntity $customer): JsonResponse
    {
        $apiToken = $customer->getApiToken();

        if ($apiToken !== null) {
            $this->entityManager->remove($apiToken);
            $this->entityManager->flush();
        }

        return new JsonResponse([
            'success' => true,
        ], Response::HTTP_OK);
    }
}

