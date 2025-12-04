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
        $content = json_encode($this->formatResponse(), \JSON_THROW_ON_ERROR);
        $headers['Content-Type'] = 'application/json';

        parent::__construct($content, $status, $headers);
    }

    /**
     * @return array<string, mixed>
     */
    final public function formatResponse(): array
    {
        return [
            ...$this->formatData(),
            'apiDescription' => $this->getApiDescription(),
        ];
    }

    /**
     * Format the response data to be JSON encoded
     *
     * @return array<string, mixed>
     */
    abstract protected function formatData(): array;

    abstract protected function getApiDescription(): ?string;
}
