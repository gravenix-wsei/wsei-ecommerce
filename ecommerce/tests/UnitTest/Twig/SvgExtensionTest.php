<?php

namespace Wsei\Ecommerce\Tests\UnitTest\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wsei\Ecommerce\Twig\SvgExtension;

class SvgExtensionTest extends TestCase
{
    public function testExtendsTwigExtension(): void
    {
        // Assert
        static::assertInstanceOf(AbstractExtension::class, $this->createSUT());
    }

    public function testGetFunctions(): void
    {
        // Arrange
        $sut = $this->createSUT();

        // Act
        $functions = $sut->getFunctions();

        // Assert
        static::assertGreaterThan(0, $functions);
        static::assertTrue(\array_reduce(
            $functions,
            fn (bool $carry, TwigFunction $item) => $carry || ($item->getName() === 'svg'), false)
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    #[DataProvider('provideLoadSvgs')]
    public function testLoadSvg(string $expectedOutput, string $path, array $attributes): void
    {
        // Arrange
        $sut = $this->createSUT();

        // Act
        $actualResult = $sut->loadSvg($path, $attributes);

        // Assert
        static::assertSame($expectedOutput, $actualResult);
    }

    /** @return iterable<string, array<mixed>> */
    public static function provideLoadSvgs(): iterable
    {
        $checkSvg = <<<'SVG'
<svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
</svg>

SVG;

        $plusSvg = <<<'SVG'
<svg data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
</svg>

SVG;

        $checkWithClass = preg_replace('/<svg([^>]*)>/i', '<svg$1 class="h-4 w-4">', $checkSvg, 1);

        $plusWithoutAria = preg_replace('/\s+aria-hidden="[^"]*"/i', '', $plusSvg, 1) ?? '';
        $plusWithAttrs = preg_replace('/<svg([^>]*)>/i', '<svg$1 aria-hidden="false" class="icon">', $plusWithoutAria, 1);

        return [
            'check raw' => [
                $checkSvg,
                'img/icons/check.svg',
                [],
            ],
            'check with class' => [
                $checkWithClass,
                'img/icons/check.svg',
                ['class' => 'h-4 w-4'],
            ],
            'plus replace aria + class' => [
                $plusWithAttrs,
                'img/icons/plus.svg',
                ['aria-hidden' => 'false', 'class' => 'icon'],
            ],
            'file not found' => [
                '<!-- SVG not found: img/icons/not-found.svg -->',
                'img/icons/not-found.svg',
                []
            ]
        ];
    }

    public function createSUT(?string $projectDir = null): SvgExtension
    {
        if ($projectDir === null) {
            $projectDir = dirname(__DIR__, 3);
        }

        return new SvgExtension($projectDir);
    }
}
