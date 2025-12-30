<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wsei\Ecommerce\Framework\Admin\Settings\SettingsProvider;

#[IsGranted('ROLE_ADMIN.CONFIG')]
#[Route('/admin/settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly SettingsProvider $settingsProvider
    ) {
    }

    #[Route('', name: 'admin.settings.index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/pages/settings/index.html.twig', [
            'settings' => $this->settingsProvider->getSettings(),
        ]);
    }
}
