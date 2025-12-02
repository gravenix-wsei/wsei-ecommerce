<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Exception\Http;

use Symfony\Component\HttpFoundation\Response;

class BadRequestException extends HttpException
{
    protected const ERROR_CODE = 'BAD_REQUEST';

    protected const DEFAULT_MESSAGE = 'Bad request';

    protected const STATUS_CODE = Response::HTTP_BAD_REQUEST;
}
