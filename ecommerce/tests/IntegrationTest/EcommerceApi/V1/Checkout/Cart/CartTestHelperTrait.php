<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Cart;

use Doctrine\ORM\EntityManagerInterface;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsCustomers;
use Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits\BuildsProducts;

trait CartTestHelperTrait
{
    use BuildsCustomers;
    use BuildsProducts;

    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
