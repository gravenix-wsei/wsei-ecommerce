<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\Admin\Docs;

use OpenApi\Generator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/admin/docs/ecommerce-api/v1')]
#[IsGranted('ROLE_ADMIN')]
class SwaggerController extends AbstractController
{
    private const SCAN_PATHS = [
        __DIR__ . '/../../EcommerceApi/V1',
        __DIR__ . '/../../../EcommerceApi/OpenApi',
        __DIR__ . '/../../../EcommerceApi/Response',
        __DIR__ . '/../../../EcommerceApi/Payload',
    ];

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly string $kernelEnvironment,
    ) {
    }

    #[Route('/swagger', name: 'admin.docs.swagger')]
    public function swagger(): Response
    {
        return $this->render('admin/pages/docs/swagger.html.twig');
    }

    #[Route('/openapi.json', name: 'admin.docs.openapi_json')]
    public function openapiJson(): JsonResponse
    {
        $cacheKey = 'ecommerce_api_v1_openapi_spec';

        // In dev environment, always regenerate
        if ($this->kernelEnvironment === 'dev') {
            $spec = $this->generateSpec();
        } else {
            $spec = $this->cache->get($cacheKey, function (ItemInterface $item): string {
                $item->expiresAfter(3600); // Cache for 1 hour

                return $this->generateSpec();
            });
        }

        return new JsonResponse($spec, json: true);
    }

    private function generateSpec(): string
    {
        $openapi = Generator::scan(self::SCAN_PATHS);

        return $openapi->toJson();
    }
}

