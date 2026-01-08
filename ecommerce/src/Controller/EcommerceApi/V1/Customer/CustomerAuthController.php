<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\BadRequestException;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\InvalidCredentialsException;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\ApiTokenResponse;
use Wsei\Ecommerce\EcommerceApi\Response\SuccessResponse;
use Wsei\Ecommerce\Entity\ApiToken;
use Wsei\Ecommerce\Entity\Customer as CustomerEntity;
use Wsei\Ecommerce\Repository\CustomerRepository;

#[OA\Schema(
    schema: 'LoginPayload',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'customer@example.com'),
        new OA\Property(property: 'password', type: 'string', example: 'password123'),
    ]
)]
#[Route('/ecommerce/api/v1/customer')]
#[OA\Tag(name: 'Customer')]
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
    #[OA\Post(
        path: '/customer/login',
        summary: 'Customer login',
        tags: ['Customer'],
        description: 'Authenticate customer and receive API token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginPayload')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiTokenResponse')
            ),
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    public function login(Request $request): ApiTokenResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            throw new BadRequestException('Email and password are required');
        }

        $customer = $this->customerRepository->findOneBy([
            'email' => $data['email'],
        ]);

        if ($customer === null || !$this->passwordHasher->isPasswordValid($customer, $data['password'])) {
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
            $apiToken->setToken(ApiToken::generate());
            $apiToken->setCustomer($customer);
            $this->entityManager->persist($apiToken);
        }

        $this->entityManager->flush();

        return new ApiTokenResponse($apiToken);
    }

    #[Route('/logout', name: 'ecommerce_api.customer.logout', methods: ['POST'])]
    #[OA\Post(
        path: '/customer/logout',
        summary: 'Customer logout',
        tags: ['Customer'],
        description: 'Invalidate current API token',
        security: [['ApiToken' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function logout(CustomerEntity $customer): Response
    {
        $apiToken = $customer->getApiToken();

        if ($apiToken !== null) {
            $this->entityManager->remove($apiToken);
            $this->entityManager->flush();
        }

        return new SuccessResponse();
    }
}
