<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'WSEI Ecommerce API',
    description: 'E-commerce API for managing products, categories, cart, orders and customer accounts'
)]
#[OA\Server(
    url: '/ecommerce/api/v1',
    description: 'API V1'
)]
#[OA\SecurityScheme(
    securityScheme: 'ApiToken',
    type: 'apiKey',
    in: 'header',
    name: 'wsei-ecommerce-token',
    description: 'API token obtained from /customer/login endpoint'
)]
#[OA\Tag(name: 'Cart', description: 'Shopping cart operations')]
#[OA\Tag(name: 'Order', description: 'Order management')]
#[OA\Tag(name: 'Category', description: 'Product categories')]
#[OA\Tag(name: 'Product', description: 'Product catalog')]
#[OA\Tag(name: 'Customer', description: 'Customer authentication')]
#[OA\Tag(name: 'Address', description: 'Customer addresses')]
#[OA\Tag(name: 'CustomerOrder', description: 'Customer order history')]
class OpenApiInfo
{
}

