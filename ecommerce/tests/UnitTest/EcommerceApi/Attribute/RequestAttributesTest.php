<?php declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\EcommerceApi\Attribute;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Wsei\Ecommerce\EcommerceApi\Attribute\RequestAttributes;
use Wsei\Ecommerce\Entity\Customer;

class RequestAttributesTest extends TestCase
{
    #[DataProvider('provideIsEcommerceApi')]
    public function testIsEcommerceApi(bool $expectedResult, array $attributes): void
    {
        // Arrange
        $request = new Request(attributes: $attributes);

        // Act
        $result = RequestAttributes::isEcommerceApi($request);

        // Assert
        static::assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{expectedResult: bool, attributes: array<string, mixed>}>
     */
    public static function provideIsEcommerceApi(): array
    {
        return [
            'is ecommerce api' => [true, [RequestAttributes::IS_ECOMMERCE_API => true]],
            'is not ecommerce api' => [false, [RequestAttributes::IS_ECOMMERCE_API => false]],
            'is empty' => [false, []],
            'is wrong key' => [false, ['ecommerce_api' => true]],
        ];
    }

    public function testExtractAuthenticatedCustomer(): void
    {
        // Arrange
        $mockedCustomer = $this->createMock(Customer::class);
        $request = new Request(attributes: [RequestAttributes::AUTHENTICATED_CUSTOMER => $mockedCustomer]);

        // Act
        $Customer = RequestAttributes::extractAuthenticatedCustomer($request);

        // Assert
        static::assertSame($mockedCustomer, $Customer);
    }

    public function testExtractAuthenticatedCustomerNotLoggedIn(): void
    {
        // Arrange
        $request = new Request();

        // Act
        $customer = RequestAttributes::extractAuthenticatedCustomer($request);

        // Assert
        static::assertNull($customer);
    }
}
