<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response;

class SuccessResponse extends EcommerceResponse
{
    public function __construct()
    {
        parent::__construct(self::HTTP_NO_CONTENT);
    }

    protected function formatResponse(): array
    {
        return [];
    }

    protected function getApiDescription(): ?string
    {
        return null;
    }
}
