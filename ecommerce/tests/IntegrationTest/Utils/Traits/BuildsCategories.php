<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Wsei\Ecommerce\Entity\Category;

trait BuildsCategories
{
    private static int $categoryNameCounter = 0;

    abstract protected function getEntityManager(): EntityManagerInterface;

    protected function createCategory(?string $name = null): Category
    {
        if ($name === null) {
            ++self::$categoryNameCounter;
            $name = 'Category ' . self::$categoryNameCounter;
        }

        $category = new Category();
        $category->setName($name);

        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();

        return $category;
    }
}
