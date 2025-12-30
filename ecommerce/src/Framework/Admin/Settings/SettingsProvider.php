<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Admin\Settings;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final readonly class SettingsProvider
{
    /**
     * @param iterable<EcommerceSettingsInterface> $settingControllers
     */
    public function __construct(
        #[AutowireIterator('wsei_ecommerce.admin.setting')]
        private iterable $settingControllers,
        private RouterInterface $router,
        private string $environment,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    /**
     * Get all registered settings as SettingItem DTOs, sorted by position and name
     * Filters settings based on user permissions
     *
     * @return array<int, SettingItem>
     */
    public function getSettings(): array
    {
        $settings = [];

        foreach ($this->settingControllers as $settingController) {
            try {
                // Check if user has required permission for this setting
                $requiredRole = $settingController->getRequiredRole();
                if ($requiredRole !== null && !$this->authorizationChecker->isGranted($requiredRole)) {
                    continue; // Skip settings user doesn't have permission for
                }

                $routeName = $settingController->getPathEntrypointName();
                $url = $this->router->generate($routeName);

                $settings[] = SettingItem::fromController($settingController, $url);
            } catch (\Exception $e) {
                // Skip settings with invalid routes in production
                if ($this->environment === 'dev') {
                    throw $e;
                }
            }
        }

        usort($settings, fn (SettingItem $a, SettingItem $b): int => $a->compareTo($b));

        return $settings;
    }
}
