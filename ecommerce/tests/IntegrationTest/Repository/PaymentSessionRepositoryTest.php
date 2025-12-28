<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Entity\OrderAddress;
use Wsei\Ecommerce\Entity\PaymentSession;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;
use Wsei\Ecommerce\Repository\PaymentSessionRepository;

class PaymentSessionRepositoryTest extends KernelTestCase
{
    private PaymentSessionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->repository = static::getContainer()->get(PaymentSessionRepository::class);
    }

    public function testCanFindPaymentSessionByToken(): void
    {
        // Arrange
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $order = $this->createTestOrder($em);

        $session = new PaymentSession();
        $session->setOrder($order);
        $session->setReturnUrl('https://example.com');
        $token = $session->getToken();

        $em->persist($session);
        $em->flush();

        // Act
        $found = $this->repository->findByToken($token);

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals($token, $found->getToken());
    }

    public function testFindByTokenReturnsNullForNonExistentToken(): void
    {
        // Act
        $result = $this->repository->findByToken('non_existent_token_12345');

        // Assert
        $this->assertNull($result);
    }

    public function testCanFindValidPaymentSessionByToken(): void
    {
        // Arrange
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $order = $this->createTestOrder($em);

        $session = new PaymentSession();
        $session->setOrder($order);
        $session->setReturnUrl('https://example.com');
        $session->setExpiresAt(new \DateTime('+1 hour'));
        $token = $session->getToken();

        $em->persist($session);
        $em->flush();

        // Act
        $found = $this->repository->findValidByToken($token);

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals($token, $found->getToken());
        $this->assertTrue($found->isActive());
    }

    public function testFindValidByTokenReturnsNullForExpiredSession(): void
    {
        // Arrange
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $order = $this->createTestOrder($em);

        $session = new PaymentSession();
        $session->setOrder($order);
        $session->setReturnUrl('https://example.com');
        $session->setExpiresAt(new \DateTime('-1 hour')); // Expired
        $token = $session->getToken();

        $em->persist($session);
        $em->flush();

        // Act
        $found = $this->repository->findValidByToken($token);

        // Assert
        $this->assertNull($found);
    }

    public function testFindValidByTokenReturnsNullForCancelledSession(): void
    {
        // Arrange
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $order = $this->createTestOrder($em);

        $session = new PaymentSession();
        $session->setOrder($order);
        $session->setReturnUrl('https://example.com');
        $session->cancel();
        $token = $session->getToken();

        $em->persist($session);
        $em->flush();

        // Act
        $found = $this->repository->findValidByToken($token);

        // Assert
        $this->assertNull($found);
    }

    public function testCanFindActiveSessionsByOrder(): void
    {
        // Arrange
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $order = $this->createTestOrder($em);

        $session1 = new PaymentSession();
        $session1->setOrder($order);
        $session1->setReturnUrl('https://example.com/1');

        $session2 = new PaymentSession();
        $session2->setOrder($order);
        $session2->setReturnUrl('https://example.com/2');

        $session3 = new PaymentSession();
        $session3->setOrder($order);
        $session3->setReturnUrl('https://example.com/3');
        $session3->cancel();

        $em->persist($session1);
        $em->persist($session2);
        $em->persist($session3);
        $em->flush();

        // Act
        $activeSessions = $this->repository->findActiveByOrder($order);

        // Assert
        $this->assertCount(2, $activeSessions);
        foreach ($activeSessions as $session) {
            $this->assertTrue($session->isActive());
        }
    }

    public function testCanCancelActiveSessionsForOrder(): void
    {
        // Arrange
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $order = $this->createTestOrder($em);

        $session1 = new PaymentSession();
        $session1->setOrder($order);
        $session1->setReturnUrl('https://example.com/1');

        $session2 = new PaymentSession();
        $session2->setOrder($order);
        $session2->setReturnUrl('https://example.com/2');

        $em->persist($session1);
        $em->persist($session2);
        $em->flush();

        // Act
        $this->repository->cancelActiveSessionsForOrder($order);
        $em->clear();

        // Assert
        $sessions = $this->repository->findActiveByOrder($order);
        $this->assertCount(0, $sessions);
    }

    public function testCancelActiveSessionsDoesNotAffectOtherOrders(): void
    {
        // Arrange
        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $order1 = $this->createTestOrder($em);
        $order2 = $this->createTestOrder($em);

        $session1 = new PaymentSession();
        $session1->setOrder($order1);
        $session1->setReturnUrl('https://example.com/1');

        $session2 = new PaymentSession();
        $session2->setOrder($order2);
        $session2->setReturnUrl('https://example.com/2');

        $em->persist($session1);
        $em->persist($session2);
        $em->flush();

        // Act
        $this->repository->cancelActiveSessionsForOrder($order1);
        $em->clear();

        // Assert
        $order1Sessions = $this->repository->findActiveByOrder($order1);
        $order2Sessions = $this->repository->findActiveByOrder($order2);

        $this->assertCount(0, $order1Sessions);
        $this->assertCount(1, $order2Sessions);
    }

    private function createTestOrder(EntityManagerInterface $em): Order
    {
        $customer = new Customer();
        $customer->setEmail('test' . uniqid() . '@example.com');
        $customer->setPassword('password');
        $customer->setFirstName('Test');
        $customer->setLastName('Customer');

        $address = new OrderAddress();
        $address->setFirstName('John');
        $address->setLastName('Doe');
        $address->setStreet('Test Street');
        $address->setCity('Test City');
        $address->setZipcode('12345');
        $address->setCountry('PL');

        $order = new Order();
        $order->setCustomer($customer);
        $order->setOrderNumber('ORD' . uniqid());
        $order->setStatus(OrderStatus::NEW);
        $order->setTotalPriceNet('100.00');
        $order->setTotalPriceGross('123.00');
        $order->setOrderAddress($address);

        $em->persist($customer);
        $em->persist($order);
        $em->flush();

        return $order;
    }
}
