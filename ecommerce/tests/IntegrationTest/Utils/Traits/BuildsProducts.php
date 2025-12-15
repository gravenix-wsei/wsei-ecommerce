<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Wsei\Ecommerce\Entity\Product;

trait BuildsProducts
{
    private static int $productNameCounter = 0;

    abstract protected function getEntityManager(): EntityManagerInterface;

    protected function createProduct(
        ?string $name = null,
        int $stock = 100,
        string $priceNet = '10.00',
        string $priceGross = '12.30'
    ): Product {
        if ($name === null) {
            ++self::$productNameCounter;
            $name = 'Test Product ' . self::$productNameCounter;
        }

        $product = new Product();
        $product->setName($name);
        $product->setDescription('Test description for ' . $name);
        $product->setStock($stock);
        $product->setPriceNet($priceNet);
        $product->setPriceGross($priceGross);

        $this->getEntityManager()->persist($product);
        $this->getEntityManager()->flush();

        return $product;
    }
}

