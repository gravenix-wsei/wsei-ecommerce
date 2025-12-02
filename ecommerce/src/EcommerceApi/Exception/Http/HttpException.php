<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Exception\Http;

use Symfony\Component\HttpFoundation\Response;

abstract class HttpException extends \Exception
{
    protected const ERROR_CODE = 'INTERNAL_SERVER_ERROR';
    protected const DEFAULT_MESSAGE = 'An internal server error occurred';
    protected const STATUS_CODE = Response::HTTP_INTERNAL_SERVER_ERROR;

    public function __construct(
        string $message = '',
        ?\Throwable $previous = null
    ) {
        $finalMessage = $message ?: static::DEFAULT_MESSAGE;
        parent::__construct($finalMessage, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return static::ERROR_CODE;
    }

    public function getStatusCode(): int
    {
        return static::STATUS_CODE;
    }

    public function getError(): string
    {
        return Response::$statusTexts[static::STATUS_CODE] ?? 'Error';
    }

    public function toArray(): array
    {
        return [
            'error' => $this->getError(),
            'message' => $this->getMessage(),
            'errorCode' => $this->getErrorCode(),
        ];
    }
}

