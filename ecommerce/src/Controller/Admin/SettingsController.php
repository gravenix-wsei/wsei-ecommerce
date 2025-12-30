<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wsei\Ecommerce\Framework\Admin\Settings\EcommerceSettingsInterface;
use Wsei\Ecommerce\Framework\Admin\Settings\SettingItem;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/settings')]
class SettingsController extends AbstractController
{
    /**
     * @param iterable<EcommerceSettingsInterface> $settingControllers
     */
    public function __construct(
        #[AutowireIterator('wsei_ecommerce.admin.setting')]
        private readonly iterable $settingControllers,
        private readonly RouterInterface $router
    ) {
    }

    #[Route('', name: 'admin.settings.index', methods: ['GET'])]
    public function index(): Response
    {
        $settings = $this->buildSettingsList();

        return $this->render('admin/pages/settings/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    /**
     * @return array<int, SettingItem>
     */
    private function buildSettingsList(): array
    {
        $settings = [];

        foreach ($this->settingControllers as $settingController) {
            try {
                $routeName = $settingController->getPathEntrypointName();
                $url = $this->router->generate($routeName);

                $settings[] = SettingItem::fromController($settingController, $url);
            } catch (\Exception $e) {
                // Skip settings with invalid routes in production
                if ($this->getParameter('kernel.environment') === 'dev') {
                    throw $e;
                }
            }
        }

        usort($settings, fn (SettingItem $a, SettingItem $b) => $a->compareTo($b));

        return $settings;
    }
}
