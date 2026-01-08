<?php declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\EcommerceApi\Attribute;

use PHPUnit\Framework\TestCase;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;

class PublicAccessTest extends TestCase
{
    public function testIsAttributeDefined(): void
    {
        // Act
        $class = new PublicAccess();
        $reflection = new \ReflectionClass($class);

        // Assert
        $attribute = $reflection->getAttributes(\Attribute::class)[0] ?? null;
        static::assertNotNull($attribute);
        $target = $attribute->getArguments()[0] ?? 0;
        static::assertGreaterThan(0, ($target) & \Attribute::TARGET_METHOD);
    }
}
