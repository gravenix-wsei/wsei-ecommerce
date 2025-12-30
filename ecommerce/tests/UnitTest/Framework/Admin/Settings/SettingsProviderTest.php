<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Framework\Admin\Settings;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Wsei\Ecommerce\Framework\Admin\Settings\EcommerceSettingsInterface;
use Wsei\Ecommerce\Framework\Admin\Settings\SettingItem;
use Wsei\Ecommerce\Framework\Admin\Settings\SettingsProvider;

class SettingsProviderTest extends TestCase
{
    private RouterInterface&MockObject $router;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
    }

    /**
     * @param array<EcommerceSettingsInterface> $settingControllers
     * @param array<string, string> $routeMap
     * @param array<int, array{name: string, position: int, icon: string}> $expectedSettings
     */
    #[DataProvider('settingsListProvider')]
    public function testGetSettingsReturnsCorrectSettingItems(
        array $settingControllers,
        array $routeMap,
        array $expectedSettings
    ): void {
        // Arrange
        $this->configureRouterWithMap($routeMap);
        $provider = new SettingsProvider($settingControllers, $this->router, 'test');

        // Act
        $settings = $provider->getSettings();

        // Assert
        $this->assertCount(count($expectedSettings), $settings);
        foreach ($expectedSettings as $index => $expected) {
            $this->assertInstanceOf(SettingItem::class, $settings[$index]);
            $this->assertSame($expected['name'], $settings[$index]->name);
            $this->assertSame($expected['position'], $settings[$index]->position);
            $this->assertSame($expected['icon'], $settings[$index]->icon);
        }
    }


    /**
     * @param array<EcommerceSettingsInterface> $settingControllers
     * @param array<string, string> $routeMap
     * @param array<int, string> $expectedOrder
     */
    #[DataProvider('sortingScenarioProvider')]
    public function testGetSettingsSortsCorrectly(
        array $settingControllers,
        array $routeMap,
        array $expectedOrder
    ): void {
        // Arrange
        $this->configureRouterWithMap($routeMap);
        $provider = new SettingsProvider($settingControllers, $this->router, 'test');

        // Act
        $settings = $provider->getSettings();

        // Assert
        $actualNames = array_map(fn (SettingItem $item): string => $item->name, $settings);
        $this->assertSame($expectedOrder, $actualNames);
    }

    public function testGetSettingsHandlesInvalidRoutesInProduction(): void
    {
        // Arrange
        $validSetting = $this->createSettingMock('Valid', 'valid.svg', 'admin.settings.valid.index', 10);
        $invalidSetting = $this->createSettingMock('Invalid', 'invalid.svg', 'admin.settings.invalid.index', 20);

        $this->router->method('generate')
            ->willReturnCallback(function (string $route) {
                if ($route === 'admin.settings.invalid.index') {
                    throw new RouteNotFoundException('Route not found');
                }
                return '/admin/settings/valid';
            });

        $provider = new SettingsProvider([$validSetting, $invalidSetting], $this->router, 'prod');

        // Act
        $settings = $provider->getSettings();

        // Assert - only valid setting should be returned
        $this->assertCount(1, $settings);
        $this->assertInstanceOf(SettingItem::class, $settings[0]);
        $this->assertSame('Valid', $settings[0]->name);
    }

    public function testGetSettingsReturnsEmptyArrayWhenNoSettings(): void
    {
        // Arrange
        $provider = new SettingsProvider([], $this->router, 'test');

        // Act
        $settings = $provider->getSettings();

        // Assert
        $this->assertIsArray($settings);
        $this->assertEmpty($settings);
    }

    public function testSettingItemFactoryMethodCreatesCorrectInstance(): void
    {
        // Arrange
        $settingController = $this->createSettingMock(
            'Test Setting',
            'test.svg',
            'admin.settings.test.index',
            50,
            'Test description'
        );
        $url = '/admin/settings/test';

        // Act
        $settingItem = SettingItem::fromController($settingController, $url);

        // Assert
        $this->assertInstanceOf(SettingItem::class, $settingItem);
        $this->assertSame('Test Setting', $settingItem->name);
        $this->assertSame('test.svg', $settingItem->icon);
        $this->assertSame($url, $settingItem->url);
        $this->assertSame(50, $settingItem->position);
        $this->assertSame('Test description', $settingItem->description);
    }

    public function testSettingItemCompareTo(): void
    {
        // Arrange
        $item1 = new SettingItem('Alpha', null, 'a.svg', '/a', 100);
        $item2 = new SettingItem('Beta', null, 'b.svg', '/b', 100);
        $item3 = new SettingItem('Gamma', null, 'g.svg', '/g', 50);

        // Act & Assert - position takes precedence
        $this->assertLessThan(0, $item3->compareTo($item1));
        $this->assertGreaterThan(0, $item1->compareTo($item3));

        // Act & Assert - alphabetical when positions equal
        $this->assertLessThan(0, $item1->compareTo($item2));
        $this->assertGreaterThan(0, $item2->compareTo($item1));
    }


    /**
     * @return array<string, array{settingControllers: array<EcommerceSettingsInterface>, routeMap: array<string, string>, expectedSettings: array<int, array{name: string, position: int, icon: string}>}>
     */
    public static function settingsListProvider(): array
    {
        return [
            'single setting' => [
                'settingControllers' => [
                    self::createStaticSettingMock('General', 'settings.svg', 'admin.settings.general.index', 10),
                ],
                'routeMap' => [
                    'admin.settings.general.index' => '/admin/settings/general',
                ],
                'expectedSettings' => [
                    ['name' => 'General', 'position' => 10, 'icon' => 'settings.svg'],
                ],
            ],
            'multiple settings sorted by position' => [
                'settingControllers' => [
                    self::createStaticSettingMock('Payment', 'payment.svg', 'admin.settings.payment.index', 100),
                    self::createStaticSettingMock('General', 'general.svg', 'admin.settings.general.index', 10),
                    self::createStaticSettingMock('Shipping', 'shipping.svg', 'admin.settings.shipping.index', 150),
                ],
                'routeMap' => [
                    'admin.settings.general.index' => '/admin/settings/general',
                    'admin.settings.payment.index' => '/admin/settings/payment',
                    'admin.settings.shipping.index' => '/admin/settings/shipping',
                ],
                'expectedSettings' => [
                    ['name' => 'General', 'position' => 10, 'icon' => 'general.svg'],
                    ['name' => 'Payment', 'position' => 100, 'icon' => 'payment.svg'],
                    ['name' => 'Shipping', 'position' => 150, 'icon' => 'shipping.svg'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{settingControllers: array<EcommerceSettingsInterface>, routeMap: array<string, string>, expectedOrder: array<int, string>}>
     */
    public static function sortingScenarioProvider(): array
    {
        return [
            'sort by position ascending' => [
                'settingControllers' => [
                    self::createStaticSettingMock('C Setting', 'c.svg', 'admin.settings.c.index', 30),
                    self::createStaticSettingMock('A Setting', 'a.svg', 'admin.settings.a.index', 10),
                    self::createStaticSettingMock('B Setting', 'b.svg', 'admin.settings.b.index', 20),
                ],
                'routeMap' => [
                    'admin.settings.a.index' => '/admin/settings/a',
                    'admin.settings.b.index' => '/admin/settings/b',
                    'admin.settings.c.index' => '/admin/settings/c',
                ],
                'expectedOrder' => ['A Setting', 'B Setting', 'C Setting'],
            ],
            'sort alphabetically when positions equal' => [
                'settingControllers' => [
                    self::createStaticSettingMock('Zebra', 'z.svg', 'admin.settings.zebra.index', 100),
                    self::createStaticSettingMock('Alpha', 'a.svg', 'admin.settings.alpha.index', 100),
                    self::createStaticSettingMock('Beta', 'b.svg', 'admin.settings.beta.index', 100),
                ],
                'routeMap' => [
                    'admin.settings.alpha.index' => '/admin/settings/alpha',
                    'admin.settings.beta.index' => '/admin/settings/beta',
                    'admin.settings.zebra.index' => '/admin/settings/zebra',
                ],
                'expectedOrder' => ['Alpha', 'Beta', 'Zebra'],
            ],
            'mixed sorting: position then alphabetically' => [
                'settingControllers' => [
                    self::createStaticSettingMock('Payment Gateway', 'payment.svg', 'admin.settings.payment.index', 100),
                    self::createStaticSettingMock('General', 'general.svg', 'admin.settings.general.index', 10),
                    self::createStaticSettingMock('Analytics', 'analytics.svg', 'admin.settings.analytics.index', 100),
                    self::createStaticSettingMock('Shop Info', 'info.svg', 'admin.settings.info.index', 10),
                ],
                'routeMap' => [
                    'admin.settings.general.index' => '/admin/settings/general',
                    'admin.settings.info.index' => '/admin/settings/info',
                    'admin.settings.payment.index' => '/admin/settings/payment',
                    'admin.settings.analytics.index' => '/admin/settings/analytics',
                ],
                'expectedOrder' => ['General', 'Shop Info', 'Analytics', 'Payment Gateway'],
            ],
        ];
    }

    /**
     * @param array<string, string> $routeMap
     */
    private function configureRouterWithMap(array $routeMap): void
    {
        $this->router->method('generate')
            ->willReturnCallback(function (string $route) use ($routeMap) {
                if (!isset($routeMap[$route])) {
                    throw new RouteNotFoundException("Route {$route} not found");
                }
                return $routeMap[$route];
            });
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

    private static function createStaticSettingMock(
        string $name,
        string $icon,
        string $route,
        int $position,
        ?string $description = null
    ): EcommerceSettingsInterface {
        return new class($name, $icon, $route, $position, $description) implements EcommerceSettingsInterface {
            public function __construct(
                private readonly string $name,
                private readonly string $icon,
                private readonly string $route,
                private readonly int $position,
                private readonly ?string $description
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getIcon(): string
            {
                return $this->icon;
            }

            public function getDescription(): ?string
            {
                return $this->description;
            }

            public function getPosition(): int
            {
                return $this->position;
            }

            public function getPathEntrypointName(): string
            {
                return $this->route;
            }
        };
    }
}
