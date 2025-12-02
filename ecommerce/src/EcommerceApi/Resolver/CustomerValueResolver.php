<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Wsei\Ecommerce\EcommerceApi\Attribute\RequestAttributes;
use Wsei\Ecommerce\Entity\Admin\Customer;

class CustomerValueResolver implements ValueResolverInterface
{
    /**
     * @return iterable<Customer>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Check if the argument type is Customer
        if ($argument->getType() !== Customer::class) {
            return [];
        }

        // Get authenticated customer from request attributes
        $customer = RequestAttributes::extractAuthenticatedCustomer($request);

        if ($customer instanceof Customer) {
            return [$customer];
        }

        return [];
    }
}
