<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Attribute;

use Symfony\Component\HttpFoundation\Request;

class RequestAttributes
{
    public const IS_ECOMMERCE_API = 'is_ecommerce_api';

    public const API_PREFIX = '/ecommerce/api/v1/';

    public const TOKEN_HEADER = 'wsei-ecommerce-token';

    public static function isEcommerceApi(Request $request): bool
    {
        return $request->attributes->get(self::IS_ECOMMERCE_API, false);
    }
}
