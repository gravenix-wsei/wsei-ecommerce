<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Controller\Admin;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Wsei\Ecommerce\Framework\Admin\Settings\EcommerceSettingsInterface;
use Wsei\Ecommerce\Framework\Admin\Settings\SettingItem;

class SettingsControllerTest extends TestCase
{
    #[DataProvider('settingsListProvider')]
    public function testSettingItemIsCreatedCorrectlyFromController(
        string $name,
        string $icon,
        string $route,
        int $position,
        ?string $description,
        string $url
    ): void {
        // Arrange
        $controller = $this->createSettingMock($name, $icon, $route, $position, $description);

        // Act
        $settingItem = SettingItem::fromController($controller, $url);

        // Assert
        $this->assertSame($name, $settingItem->name);
        $this->assertSame($icon, $settingItem->icon);
        $this->assertSame($url, $settingItem->url);
        $this->assertSame($position, $settingItem->position);
        $this->assertSame($description, $settingItem->description);
    }

    /**
     * @param array<int, array{name: string, description: string|null, icon: string, url: string, position: int}> $items
     * @param array<int, string> $expectedOrder
     */
    #[DataProvider('sortingScenarioProvider')]
    public function testSettingItemsAreSortedByPositionThenAlphabetically(
        array $items,
        array $expectedOrder
    ): void {
        // Arrange
        $settings = array_map(
            fn (array $item): SettingItem => new SettingItem(
                $item['name'],
                $item['description'],
                $item['icon'],
                $item['url'],
                $item['position']
            ),
            $items
        );

        // Act
        usort($settings, fn (SettingItem $a, SettingItem $b): int => $a->compareTo($b));

        // Assert
        $actualNames = array_map(fn (SettingItem $item): string => $item->name, $settings);
        $this->assertSame($expectedOrder, $actualNames);
    }

    public function testCompareToSortsByPositionFirst(): void
    {
        // Arrange
        $lower = new SettingItem('B', null, 'icon.svg', '/url', 10);
        $higher = new SettingItem('A', null, 'icon.svg', '/url', 20);

        // Act
        $result = $lower->compareTo($higher);

        // Assert
        $this->assertLessThan(0, $result, 'Lower position should come first');
    }

    public function testCompareToSortsAlphabeticallyWhenPositionsAreEqual(): void
    {
        // Arrange
        $itemA = new SettingItem('Alpha', null, 'icon.svg', '/url', 100);
        $itemB = new SettingItem('Beta', null, 'icon.svg', '/url', 100);

        // Act
        $result = $itemA->compareTo($itemB);

        // Assert
        $this->assertLessThan(0, $result, 'Alpha should come before Beta');
    }

    public function testCompareToIsCaseInsensitiveForAlphabeticalSorting(): void
    {
        // Arrange
        $itemA = new SettingItem('alpha', null, 'icon.svg', '/url', 100);
        $itemB = new SettingItem('BETA', null, 'icon.svg', '/url', 100);

        // Act
        $result = $itemA->compareTo($itemB);

        // Assert
        $this->assertLessThan(0, $result, 'Alphabetical sorting should be case-insensitive');
    }

    public function testSettingItemPropertiesAreReadonly(): void
    {
        // Arrange & Act
        $item = new SettingItem('Test', 'Description', 'icon.svg', '/url', 10);

        // Assert
        $reflection = new \ReflectionClass($item);
        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), "Property {$property->getName()} should be readonly");
        }
    }

    /**
     * @return array<string, array{name: string, icon: string, route: string, position: int, description: string|null, url: string}>
     */
    public static function settingsListProvider(): array
    {
        return [
            'basic setting' => [
                'name' => 'General Settings',
                'icon' => 'settings.svg',
                'route' => 'admin.settings.general.index',
                'position' => 10,
                'description' => 'Configure general shop settings',
                'url' => '/admin/settings/general',
            ],
            'setting without description' => [
                'name' => 'Payment',
                'icon' => 'payment.svg',
                'route' => 'admin.settings.payment.index',
                'position' => 100,
                'description' => null,
                'url' => '/admin/settings/payment',
            ],
            'setting with special characters' => [
                'name' => 'SEO & Analytics',
                'icon' => 'analytics.svg',
                'route' => 'admin.settings.seo.index',
                'position' => 200,
                'description' => 'Search engine & tracking configuration',
                'url' => '/admin/settings/seo',
            ],
        ];
    }

    /**
     * @return array<string, array{items: array<int, array{name: string, description: string|null, icon: string, url: string, position: int}>, expectedOrder: array<int, string>}>
     */
    public static function sortingScenarioProvider(): array
    {
        return [
            'sort by position ascending' => [
                'items' => [
                    [
                        'name' => 'C',
                        'description' => null,
                        'icon' => 'c.svg',
                        'url' => '/c',
                        'position' => 30,
                    ],
                    [
                        'name' => 'A',
                        'description' => null,
                        'icon' => 'a.svg',
                        'url' => '/a',
                        'position' => 10,
                    ],
                    [
                        'name' => 'B',
                        'description' => null,
                        'icon' => 'b.svg',
                        'url' => '/b',
                        'position' => 20,
                    ],
                ],
                'expectedOrder' => ['A', 'B', 'C'],
            ],
            'sort alphabetically when positions equal' => [
                'items' => [
                    [
                        'name' => 'Zebra',
                        'description' => null,
                        'icon' => 'z.svg',
                        'url' => '/z',
                        'position' => 100,
                    ],
                    [
                        'name' => 'Alpha',
                        'description' => null,
                        'icon' => 'a.svg',
                        'url' => '/a',
                        'position' => 100,
                    ],
                    [
                        'name' => 'Beta',
                        'description' => null,
                        'icon' => 'b.svg',
                        'url' => '/b',
                        'position' => 100,
                    ],
                ],
                'expectedOrder' => ['Alpha', 'Beta', 'Zebra'],
            ],
            'mixed sorting' => [
                'items' => [
                    [
                        'name' => 'Payment',
                        'description' => null,
                        'icon' => 'p.svg',
                        'url' => '/p',
                        'position' => 100,
                    ],
                    [
                        'name' => 'General',
                        'description' => null,
                        'icon' => 'g.svg',
                        'url' => '/g',
                        'position' => 10,
                    ],
                    [
                        'name' => 'Analytics',
                        'description' => null,
                        'icon' => 'a.svg',
                        'url' => '/a',
                        'position' => 100,
                    ],
                    [
                        'name' => 'Shop Info',
                        'description' => null,
                        'icon' => 's.svg',
                        'url' => '/s',
                        'position' => 10,
                    ],
                ],
                'expectedOrder' => ['General', 'Shop Info', 'Analytics', 'Payment'],
            ],
            'case insensitive alphabetical sorting' => [
                'items' => [
                    [
                        'name' => 'beta',
                        'description' => null,
                        'icon' => 'b.svg',
                        'url' => '/b',
                        'position' => 100,
                    ],
                    [
                        'name' => 'ALPHA',
                        'description' => null,
                        'icon' => 'a.svg',
                        'url' => '/a',
                        'position' => 100,
                    ],
                    [
                        'name' => 'Gamma',
                        'description' => null,
                        'icon' => 'g.svg',
                        'url' => '/g',
                        'position' => 100,
                    ],
                ],
                'expectedOrder' => ['ALPHA', 'beta', 'Gamma'],
            ],
        ];
    }

    private function createSettingMock(
        string $name,
        string $icon,
        string $route,
        int $position,
        ?string $description = null
    ): EcommerceSettingsInterface {
        $setting = $this->createMock(EcommerceSettingsInterface::class);
        $setting->method('getName')->willReturn($name);
        $setting->method('getIcon')->willReturn($icon);
        $setting->method('getPathEntrypointName')->willReturn($route);
        $setting->method('getPosition')->willReturn($position);
        $setting->method('getDescription')->willReturn($description);

        return $setting;
    }
}
