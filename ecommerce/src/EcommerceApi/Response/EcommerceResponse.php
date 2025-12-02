<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response;

use Symfony\Component\HttpFoundation\Response;

abstract class EcommerceResponse extends Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(int $status = self::HTTP_OK, array $headers = [])
    {
        $data = $this->formatResponse() + ($this->getApiDescription() ? [
            'apiDescription' => $this->getApiDescription(),
        ] : []);
        $content = json_encode($data, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
        $headers['Content-Type'] = 'application/json';

        parent::__construct($content, $status, $headers);
    }

    /**
     * Format the response data to be JSON encoded
     *
     * @return array<string, mixed>
     */
    abstract protected function formatResponse(): array;

    abstract protected function getApiDescription(): ?string;
}
