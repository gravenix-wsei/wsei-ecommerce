<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Order;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsAddresses;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsCustomers;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsProducts;

abstract class AbstractOrderPlacementTest extends WebTestCase
{
    use BuildsAddresses;
    use BuildsCustomers;
    use BuildsProducts;

    protected KernelBrowser $client;

    protected ContainerInterface $container;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
        $entityManager = $this->container->get(EntityManagerInterface::class);
        $this->entityManager = $entityManager;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    protected function addItemToCart(Customer $customer, int $productId, int $quantity): void
    {
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/items', [
            'productId' => $productId,
            'quantity' => $quantity,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()?->getToken(),
        ]);
    }

    protected function placeOrder(Customer $customer, int $addressId): void
    {
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/cart/order', [
            'addressId' => $addressId,
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()?->getToken(),
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    protected function getAuthHeaders(Customer $customer): array
    {
        return [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()?->getToken(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getJsonResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        if ($content === false) {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }
}
