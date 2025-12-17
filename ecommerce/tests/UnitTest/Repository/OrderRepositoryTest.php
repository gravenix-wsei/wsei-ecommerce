<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Repository\OrderRepository;

class OrderRepositoryTest extends TestCase
{
    private OrderRepository&MockObject $repository;

    private ManagerRegistry&MockObject $registry;

    private QueryBuilder&MockObject $queryBuilder;

    /**
     * @phpstan-ignore-next-line
     */
    private Query&MockObject $query;

    protected function setUp(): void
    {
        parent::setUp();

        // Arrange
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        $this->repository = $this->getMockBuilder(OrderRepository::class)
            ->onlyMethods(['createQueryBuilder'])
            ->setConstructorArgs([$this->registry])
            ->getMock();
    }

    public function testGetNextOrderNumberReturnsFirstNumberWhenNoOrders(): void
    {
        // Arrange
        $this->repository->expects(static::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects(static::once())
            ->method('select')
            ->with('MAX(o.orderNumber)')
            ->willReturnSelf();

        $this->queryBuilder->expects(static::once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects(static::once())
            ->method('getSingleScalarResult')
            ->willReturn(null);

        // Act
        $nextNumber = $this->repository->getNextOrderNumber();

        // Assert
        $this->assertSame('00001', $nextNumber);
    }

    public function testGetNextOrderNumberIncrementsFromExistingNumber(): void
    {
        // Arrange
        $this->repository->expects(static::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects(static::once())
            ->method('select')
            ->with('MAX(o.orderNumber)')
            ->willReturnSelf();

        $this->queryBuilder->expects(static::once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects(static::once())
            ->method('getSingleScalarResult')
            ->willReturn('00042');

        // Act
        $nextNumber = $this->repository->getNextOrderNumber();

        // Assert
        $this->assertSame('00043', $nextNumber);
    }

    public function testFindAllPaginatedBuildsCorrectQuery(): void
    {
        // Arrange
        $this->repository->expects(static::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects(static::once())
            ->method('orderBy')
            ->with('o.createdAt', 'DESC')
            ->willReturnSelf();

        $this->queryBuilder->expects(static::once())
            ->method('setFirstResult')
            ->with(40) // page 3, limit 20: (3-1) * 20 = 40
            ->willReturnSelf();

        $this->queryBuilder->expects(static::once())
            ->method('setMaxResults')
            ->with(20)
            ->willReturnSelf();

        $this->queryBuilder->expects(static::once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects(static::once())
            ->method('getResult')
            ->willReturn([$this->createMock(Order::class)]);

        // Act
        $result = $this->repository->findAllPaginated(3, 20);

        // Assert
        $this->assertNotEmpty($result);
    }

    public function testCountAllBuildsCorrectQuery(): void
    {
        // Arrange
        $this->repository->expects(static::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects(static::once())
            ->method('select')
            ->with('COUNT(o.id)')
            ->willReturnSelf();

        $this->queryBuilder->expects(static::once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects(static::once())
            ->method('getSingleScalarResult')
            ->willReturn(42);

        // Act
        $count = $this->repository->countAll();

        // Assert
        $this->assertSame(42, $count);
    }
}
