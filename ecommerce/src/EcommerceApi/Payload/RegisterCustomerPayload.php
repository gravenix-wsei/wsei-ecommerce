<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Payload;

use OpenApi\Attributes as OA;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Wsei\Ecommerce\Entity\Customer;

#[OA\Schema(
    schema: 'RegisterCustomerPayload',
    required: ['email', 'password', 'firstName', 'lastName'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
        new OA\Property(property: 'password', type: 'string', minLength: 8, example: 'password123'),
        new OA\Property(property: 'firstName', type: 'string', example: 'John'),
        new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
    ]
)]
class RegisterCustomerPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required')]
        #[Assert\Email(message: 'Email must be a valid email address')]
        #[Assert\Length(max: 180)]
        public readonly string $email,
        #[Assert\NotBlank(message: 'Password is required')]
        #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long')]
        public readonly string $password,
        #[Assert\NotBlank(message: 'First name is required')]
        #[Assert\Length(max: 100)]
        public readonly string $firstName,
        #[Assert\NotBlank(message: 'Last name is required')]
        #[Assert\Length(max: 100)]
        public readonly string $lastName,
    ) {
    }

    public function createCustomer(UserPasswordHasherInterface $passwordHasher): Customer
    {
        $customer = new Customer();
        $customer->setEmail($this->email);
        $customer->setFirstName($this->firstName);
        $customer->setLastName($this->lastName);
        $customer->setPassword($passwordHasher->hashPassword($customer, $this->password));

        return $customer;
    }
}
