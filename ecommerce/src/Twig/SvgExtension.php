<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class SvgExtension extends AbstractExtension
{
    public function __construct(
        private readonly string $projectDir
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('svg', [$this, 'loadSvg'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function loadSvg(string $path, array $attributes = []): string
    {
        $filePath = $this->projectDir . '/public/' . ltrim($path, '/');

        if (! file_exists($filePath)) {
            return sprintf('<!-- SVG not found: %s -->', htmlspecialchars($path));
        }

        $svg = file_get_contents($filePath);

        if ($svg === false) {
            return sprintf('<!-- Could not read SVG: %s -->', htmlspecialchars($path));
        }

        if (! empty($attributes)) {
            $svg = $this->addAttributesToSvg($svg, $attributes);
        }

        return $svg;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function addAttributesToSvg(string $svg, array $attributes): string
    {
        if (! preg_match('/<svg([^>]*)>/i', $svg, $matches)) {
            return $svg;
        }

        $existingAttributes = $matches[1];
        $newAttributes = '';

        foreach ($attributes as $key => $value) {
            $existingAttributes = preg_replace(
                '/\s+' . preg_quote($key, '/') . '=["\'][^"\']*["\']/i',
                '',
                $existingAttributes
            );

            $newAttributes .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
        }

        return preg_replace('/<svg[^>]*>/i', '<svg' . $existingAttributes . $newAttributes . '>', $svg, 1);
    }
}
