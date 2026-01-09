<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Exception\Http;

use Symfony\Component\HttpFoundation\Response;

class CustomerAlreadyExistsException extends HttpException
{
    protected const ERROR_CODE = 'CUSTOMER_ALREADY_EXISTS';

    protected const DEFAULT_MESSAGE = 'Customer with this email already exists';

    protected const STATUS_CODE = Response::HTTP_CONFLICT;
}
