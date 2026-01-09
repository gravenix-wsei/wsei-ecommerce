<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;
use Wsei\Ecommerce\EcommerceApi\Exception\Http\CustomerAlreadyExistsException;
use Wsei\Ecommerce\EcommerceApi\Payload\RegisterCustomerPayload;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\ApiTokenResponse;
use Wsei\Ecommerce\Entity\ApiToken;
use Wsei\Ecommerce\Repository\CustomerRepository;

#[Route('/ecommerce/api/v1/customer')]
#[OA\Tag(name: 'Customer')]
class CustomerRegisterController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomerRepository $customerRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[PublicAccess]
    #[Route('/register', name: 'ecommerce_api.customer.register', methods: ['POST'])]
    #[OA\Post(
        path: '/customer/register',
        summary: 'Register new customer',
        tags: ['Customer'],
        description: 'Create a new customer account and receive API token',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RegisterCustomerPayload')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Customer registered successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiTokenResponse')
            ),
            new OA\Response(response: 400, description: 'Bad request - validation failed'),
            new OA\Response(response: 409, description: 'Customer with this email already exists'),
        ]
    )]
    public function register(
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        RegisterCustomerPayload $payload
    ): ApiTokenResponse {
        $existingCustomer = $this->customerRepository->findOneBy([
            'email' => $payload->email,
        ]);

        if ($existingCustomer !== null) {
            throw new CustomerAlreadyExistsException();
        }

        $customer = $payload->createCustomer($this->passwordHasher);

        $apiToken = new ApiToken();
        $apiToken->setToken(ApiToken::generate());
        $apiToken->setCustomer($customer);

        $this->entityManager->persist($customer);
        $this->entityManager->persist($apiToken);
        $this->entityManager->flush();

        $response = new ApiTokenResponse($apiToken);
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
