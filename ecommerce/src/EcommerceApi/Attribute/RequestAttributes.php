<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Attribute;

use Symfony\Component\HttpFoundation\Request;
use Wsei\Ecommerce\Entity\Admin\Customer;

class RequestAttributes
{
    public const IS_ECOMMERCE_API = 'is_ecommerce_api';

    public const API_PREFIX = '/ecommerce/api/v1/';

    public const TOKEN_HEADER = 'wsei-ecommerce-token';

    public const AUTHENTICATED_CUSTOMER = 'authenticated_customer';

    public static function isEcommerceApi(Request $request): bool
    {
        return $request->attributes->get(self::IS_ECOMMERCE_API, false);
    }

    public static function extractAuthenticatedCustomer(Request $request): ?Customer
    {
        return $request->attributes->get(self::AUTHENTICATED_CUSTOMER);
    }
}
