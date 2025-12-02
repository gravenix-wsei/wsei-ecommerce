<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Exception\Http;

use Symfony\Component\HttpFoundation\Response;

class InvalidCredentialsException extends HttpException
{
    protected const ERROR_CODE = 'INVALID_CREDENTIALS';
    protected const DEFAULT_MESSAGE = 'Invalid credentials';
    protected const STATUS_CODE = Response::HTTP_UNAUTHORIZED;
}

