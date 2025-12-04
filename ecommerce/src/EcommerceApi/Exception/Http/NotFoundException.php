<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Exception\Http;

use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends HttpException
{
    protected const ERROR_CODE = 'NOT_FOUND';

    protected const DEFAULT_MESSAGE = 'Resource not found';

    protected const STATUS_CODE = Response::HTTP_NOT_FOUND;
}
