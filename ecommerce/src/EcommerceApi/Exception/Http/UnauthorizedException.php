<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Exception\Http;

use Symfony\Component\HttpFoundation\Response;

class UnauthorizedException extends HttpException
{
    protected const ERROR_CODE = 'UNAUTHORIZED';
    protected const DEFAULT_MESSAGE = 'Invalid or expired token';
    protected const STATUS_CODE = Response::HTTP_UNAUTHORIZED;
}

