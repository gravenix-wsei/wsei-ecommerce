# GitHub Copilot Instructions - Symfony E-commerce

> **Note:** This file contains project-specific instructions. See repository root `.github/copilot-instructions.md` for global guidelines.

## Project Overview
This is a **Symfony 7.4** e-commerce application. Follow these guidelines when generating code suggestions.

### Docker Environment
- **Always run Symfony commands inside the PHP Docker container**
- Use `make php-exec CMD="..."` for executing commands in the container
- Example: `make php-exec CMD="bin/console cache:clear"`

#### Command Execution
Execute commands directly in the PHP container:
```bash
# Clear Symfony cache
make php-exec CMD="bin/console cache:clear"

# Run migrations
make php-exec CMD="bin/console doctrine:migrations:migrate"

# Create entity
make php-exec CMD="bin/console make:entity Product"

# Install composer packages
make php-exec CMD="composer require package-name"

# Run PHPStan
make php-exec CMD="vendor/bin/phpstan analyse"

# Run ECS
make php-exec CMD="vendor/bin/ecs check"

# Multiple commands can be chained with &&
make php-exec CMD="composer install && bin/console cache:clear"
```

#### Running Symfony Console Commands
```bash
make php-exec CMD="bin/console make:entity"
make php-exec CMD="bin/console make:crud"
make php-exec CMD="bin/console doctrine:migrations:migrate"
make php-exec CMD="bin/console doctrine:schema:validate"
```

#### Running Composer Commands
```bash
make php-exec CMD="composer install"
make php-exec CMD="composer require package-name"
make php-exec CMD="composer update"
make php-exec CMD="composer dump-autoload"
```

#### Quick Docker Command Reference
```bash
# Start containers
make up

# Stop containers
make stop

# View logs
make logs

# Restart containers
make restart

# Build containers
make build

# Execute command in PHP container
make php-exec CMD="bin/console cache:clear"
```

#### Development Workflow
```bash
# Start the Docker environment
make up

# Run commands as needed
make php-exec CMD="bin/console cache:clear"
make php-exec CMD="composer install"
make php-exec CMD="bin/console doctrine:migrations:migrate"

# Stop the environment when done
make stop
```

### SVG Icons
- **Never use inline SVG code in Twig templates**
- All SVG icons must be stored as external files in `public/img/icons/`
- Use the Twig `svg()` function to include icons: `{{ svg('img/icons/icon-name.svg', {class: 'icon'}) }}`
- Common available icons:
  - `plus.svg` - Add/Create actions
  - `eye.svg` - View/Show actions
  - `pencil.svg` - Edit actions
  - `trash.svg` - Delete actions
  - `check.svg` - Save/Confirm actions
  - `x-circle.svg` - Cancel actions
  - `arrow-left.svg` - Back/Return navigation
  - `dashboard.svg` - Dashboard navigation
  - `logout.svg` - Logout action
  - `category.svg` - Category navigation
  - `products.svg` - Products navigation
- When creating new templates, always use external SVG files instead of inline SVG markup

### Administration Scoping
- **Always scope administration features under `/admin` path** for routes
- **Always use `Admin` or `Administration` namespace** for admin-related code
- Examples:
  - Routes: `/admin/login`, `/admin/dashboard`, `/admin/products`
  - Namespaces: `Wsei\Ecommerce\Controller\Admin`, `Wsei\Ecommerce\Service\Administration`
  - Templates: `templates/admin/`, `templates/admin/security/`
  - CSS: `public/css/admin/`
  - Providers: `admin_user_provider` not `app_user_provider`
- Keep admin functionality clearly separated from public-facing features

### Admin Settings Plugin System
The application uses a **plugin-like architecture** for admin settings, allowing easy extension without modifying core code.

#### Settings Controller Architecture
- **Main Controller:** `Wsei\Ecommerce\Controller\Admin\SettingsController` - Discovers and displays all registered settings
- **Setting Controllers:** `Wsei\Ecommerce\Controller\Admin\Settings\{SettingName}\*` - Individual setting implementations in their own directories
- **Interface:** `Wsei\Ecommerce\Framework\Admin\Settings\EcommerceSettingsInterface` - Required interface for all settings

#### Directory Structure
Each setting should be organized in its own directory:
```
src/Controller/Admin/Settings/
├── Example/
│   └── ExampleSettingController.php
├── General/
│   └── GeneralSettingController.php
└── Payment/
    └── PaymentSettingController.php

templates/admin/pages/settings/
├── base.html.twig (shared base template)
├── index.html.twig (settings grid)
├── example/
│   └── index.html.twig
├── general/
│   └── index.html.twig
└── payment/
    └── index.html.twig
```

#### Creating a New Setting
1. Create directory in `src/Controller/Admin/Settings/{SettingName}/`
2. Create controller class implementing `EcommerceSettingsInterface`
3. Create template directory in `templates/admin/pages/settings/{settingname}/`
4. Create `index.html.twig` extending the settings base template
5. Define route following the pattern: `admin.settings.{name}.index`
6. Add `#[IsGranted('ROLE_ADMIN')]` security attribute
7. Auto-tagging happens automatically via service configuration

**Example Setting Controller:**
```php
<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Controller\Admin\Settings\General;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wsei\Ecommerce\Framework\Admin\Settings\EcommerceSettingsInterface;

#[Route('/admin/settings/general', name: 'admin.settings.general.')]
#[IsGranted('ROLE_ADMIN')]
class GeneralSettingController extends AbstractController implements EcommerceSettingsInterface
{
    public function getName(): string
    {
        return 'General Settings';
    }

    public function getIcon(): string
    {
        return 'settings.svg'; // Icon filename from public/img/icons/
    }

    public function getDescription(): ?string
    {
        return 'Configure general shop information and preferences';
    }

    public function getPosition(): int
    {
        return 10; // Lower numbers appear first; when equal, alphabetical sorting applies
    }

    public function getPathEntrypointName(): string
    {
        return 'admin.settings.general.index'; // Must match route name
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/pages/settings/general/index.html.twig');
    }
}
```

#### Interface Methods
```php
interface EcommerceSettingsInterface
{
    // Display name shown in settings grid
    public function getName(): string;
    
    // Icon filename from public/img/icons/ (e.g., 'settings.svg')
    public function getIcon(): string;
    
    // Optional description shown below the name
    public function getDescription(): ?string;
    
    // Display order (lower = first; ties sorted alphabetically)
    public function getPosition(): int;
    
    // Route name for this setting (must follow admin.settings.* pattern)
    public function getPathEntrypointName(): string;
}
```

#### SettingItem DTO
The `SettingItem` class is a Data Transfer Object (DTO) that represents a single setting item in the admin panel. It provides:

- **Type-safe property access**: All properties are typed and readonly
- **Factory method**: `SettingItem::fromController()` creates instances from `EcommerceSettingsInterface`
- **Built-in sorting**: `compareTo()` method for position-based and alphabetical sorting

```php
final class SettingItem
{
    public readonly string $name;
    public readonly ?string $description;
    public readonly string $icon;
    public readonly string $url;
    public readonly int $position;

    public static function fromController(
        EcommerceSettingsInterface $controller,
        string $url
    ): self;

    public function compareTo(self $other): int;
}
```
}
```

#### Route Naming Convention
- **Pattern:** `admin.settings.{name}`
- **Examples:**
  - `admin.settings.general` - General shop settings
  - `admin.settings.payment` - Payment configuration
  - `admin.settings.shipping` - Shipping options
  - `admin.settings.tax` - Tax configuration

#### Position Guidelines
- **0-99:** Core/essential settings (e.g., General, Shop Info)
- **100-199:** Business logic settings (e.g., Payment, Shipping)
- **200-299:** Advanced/optional settings (e.g., SEO, Analytics)
- **300+:** Extension/plugin settings

When positions are equal, settings are sorted alphabetically by name.

#### Service Configuration
All controllers implementing `EcommerceSettingsInterface` are automatically tagged with `wsei_ecommerce.admin.setting` via auto-configuration in `config/services.yaml`:

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
    
    # Auto-tag all setting controllers that implement EcommerceSettingsInterface
    _instanceof:
        Wsei\Ecommerce\Framework\Admin\Settings\EcommerceSettingsInterface:
            tags: ['wsei_ecommerce.admin.setting']
```

The configuration uses Symfony's `_instanceof` feature to automatically tag any service implementing the `EcommerceSettingsInterface`, making new settings discoverable without manual registration.

#### Template Structure
- **Settings Index:** `templates/admin/pages/settings/index.html.twig` - Grid layout showing all settings
- **Settings Base:** `templates/admin/pages/settings/base.html.twig` - Base template for all setting pages with back button
- **Individual Settings:** `templates/admin/pages/settings/{settingname}/index.html.twig` - Each setting's view in its own directory
- **Grid Layout:** 2 columns on desktop, 1 column on mobile

Each setting should have its own template directory matching the controller structure:
```
templates/admin/pages/settings/
├── base.html.twig          # Shared base template
├── index.html.twig         # Settings grid
├── example/
│   └── index.html.twig     # Example setting view
└── general/
    └── index.html.twig     # General setting view
```

All individual setting templates should extend `admin/pages/settings/base.html.twig`:

```twig
{% extends 'admin/pages/settings/base.html.twig' %}

{% block title %}Your Setting Name{% endblock %}

{% block settings_title %}Your Setting Name{% endblock %}

{% block settings_content %}
    <div class="card">
        {# Your setting content here #}
    </div>
{% endblock %}
```

The base template automatically provides:
- Back button to settings index
- Consistent header structure
- Proper admin container layout

#### Best Practices
- Keep setting controllers focused on single responsibility
- Use meaningful position values to group related settings
- Provide clear, concise descriptions for better UX
- Follow the route naming convention strictly: `admin.settings.*`
- Always use `#[IsGranted('ROLE_ADMIN')]` for security
- Store setting-specific logic in dedicated service classes
- Use appropriate icons from `public/img/icons/` directory

#### Discovery Mechanism
The main `SettingsController` automatically:
1. Injects all tagged services via `#[TaggedIterator('wsei_ecommerce.admin.setting')]`
2. Extracts metadata (name, icon, description, position) from each setting
3. Generates URLs using `RouterInterface` and `getPathEntrypointName()`
4. Sorts by position (ascending), then alphabetically by name
5. Renders settings grid with clickable cards

No manual registration required - just create a controller implementing the interface!

## Symfony-Specific Guidelines

### Framework Conventions
- Follow Symfony best practices and conventions
- Use Symfony's dependency injection container
- Leverage Symfony components (Form, Validator, Security, etc.)
- Use attributes for routing, validation, and ORM mapping
- Follow the Symfony directory structure

### Controllers
- Extend `AbstractController`
- Use constructor injection for dependencies
- Return `Response` objects from controller actions
- Use route attributes: `#[Route('/path', name: 'route_name')]`

### Services
- Auto-configure services in `services.yaml`
- Use constructor injection
- Tag services when needed
- Keep services focused on single responsibility

### Doctrine ORM
- Use attributes for entity mapping
- Define repositories as services
- Use QueryBuilder for complex queries
- Implement custom repository methods

### Forms
- Create FormType classes for forms
- Use form themes for consistent styling
- Implement CSRF protection (enabled by default)

## File Structure
- Controllers: `/src/Controller/`
- Entities: `/src/Entity/`
- Forms: `/src/Form/`
- Repositories: `/src/Repository/`
- Services: `/src/Service/`
- Templates: `/templates/`
- Public assets: `/public/`
- Configuration: `/config/`
- Migrations: `/migrations/`

## Common Patterns

### Controller Example
```php
#[Route('/product')]
class ProductController extends AbstractController {
    public function __construct(
        private ProductService $productService
    ) {}
    
    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    public function show(int $id): Response {
        $product = $this->productService->findById($id);
        
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
```

### Entity Example
```php
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\Column(length: 255)]
    private ?string $name = null;
}
```

### Service Example
```php
class ProductService {
    public function __construct(
        private ProductRepository $repository
    ) {}
    
    public function findById(int $id): ?Product {
        return $this->repository->find($id);
    }
}
```

## API Authentication

### Overview
The e-commerce application provides a token-based API authentication system for customer-facing endpoints. All API routes are scoped under `/ecommerce/api/v1/` prefix.

### Authentication Flow

#### Login
**Endpoint:** `POST /ecommerce/api/v1/customer/login`

**Request Body (JSON):**
```json
{
  "email": "customer@example.com",
  "password": "password123"
}
```

**Success Response (200 OK):**
```json
{
  "token": "Abc123XyZ...48characters...ABC",
  "expiresAt": "2024-12-02 15:30:00"
}
```

**Error Response (401 Unauthorized):**
```json
{
  "error": "Unauthorized",
  "message": "Invalid credentials"
}
```

#### Logout
**Endpoint:** `POST /ecommerce/api/v1/customer/logout`

**Headers:**
```
wsei-ecommerce-token: Abc123XyZ...48characters...ABC
```

**Success Response (200 OK):**
```json
{
  "success": true
}
```

### Token Details
- **Format:** 48-character alphanumeric string (A-Za-z0-9)
- **Expiration:** 1 hour from creation/last login
- **Behavior:** Re-login extends existing token expiration (no new token created)
- **Limitation:** One active token per customer (one-to-one relationship)
- **Lifecycle:** Token is automatically deleted when customer is deleted

### Protected Endpoints
By default, all endpoints under `/ecommerce/api/v1/` require authentication via the `wsei-ecommerce-token` header.

**Request Headers:**
```
Content-Type: application/json
wsei-ecommerce-token: Abc123XyZ...48characters...ABC
```

**401 Error Response (Invalid/Missing Token):**
```json
{
  "error": "Unauthorized",
  "message": "Invalid or expired token"
}
```

### Public Endpoints
To mark an endpoint as publicly accessible (no token required), use the `#[PublicAccess]` attribute:

```php
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;

#[PublicAccess]
#[Route('/some-public-endpoint', methods: ['POST'])]
public function publicAction(): JsonResponse
{
    // No authentication required
    return new JsonResponse(['message' => 'Public access']);
}
```

### Customer Auto-Injection
Authenticated endpoints can automatically inject the `Customer` entity into controller parameters:

```php
use Wsei\Ecommerce\Entity\Admin\Customer;

#[Route('/profile', name: 'ecommerce_api.customer.profile', methods: ['GET'])]
public function getProfile(Customer $customer): JsonResponse
{
    return new JsonResponse([
        'email' => $customer->getEmail(),
        'firstName' => $customer->getFirstName(),
        'lastName' => $customer->getLastName(),
    ]);
}
```

The `Customer` parameter is automatically resolved from the authenticated token. No manual token validation needed.

### Controller Example
```php
namespace Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess;
use Wsei\Ecommerce\Entity\Admin\Customer;

#[Route('/ecommerce/api/v1/customer')]
class CustomerProfileController extends AbstractController
{
    // Public endpoint - no authentication required
    #[PublicAccess]
    #[Route('/check', methods: ['GET'])]
    public function check(): JsonResponse
    {
        return new JsonResponse(['status' => 'API is running']);
    }
    
    // Protected endpoint - requires wsei-ecommerce-token header
    #[Route('/profile', methods: ['GET'])]
    public function profile(Customer $customer): JsonResponse
    {
        return new JsonResponse([
            'id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'fullName' => $customer->getFullName(),
        ]);
    }
    
    // Protected endpoint with business logic
    #[Route('/addresses', methods: ['GET'])]
    public function addresses(Customer $customer): JsonResponse
    {
        $addresses = $customer->getAddresses()->map(function ($address) {
            return [
                'id' => $address->getId(),
                'street' => $address->getStreet(),
                'city' => $address->getCity(),
                'postalCode' => $address->getPostalCode(),
            ];
        })->toArray();
        
        return new JsonResponse(['addresses' => $addresses]);
    }
}
```

### API Namespaces

**Controllers:**
All API controllers should be placed in the namespace:
```
Wsei\Ecommerce\Controller\EcommerceApi\V1
```

For customer-specific endpoints:
```
Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer
```

**EcommerceApi Components:**
API-specific components (attributes, subscribers, resolvers) are scoped under:
```
Wsei\Ecommerce\EcommerceApi\
```

This includes:
- `Wsei\Ecommerce\EcommerceApi\Attribute\PublicAccess` - Attribute for public endpoints
- `Wsei\Ecommerce\EcommerceApi\EventSubscriber\ApiTokenAuthenticationSubscriber` - Token validation
- `Wsei\Ecommerce\EcommerceApi\Resolver\CustomerValueResolver` - Customer auto-injection

**Configuration:**
EcommerceApi services are configured in:
```
config/packages/ecommerce_api.yaml
```

### Token Generation
Tokens are generated using the `ApiTokenHelper` utility class:

```php
use Wsei\Ecommerce\Entity\Admin\ApiToken;
use Wsei\Ecommerce\Utility\ApiTokenHelper;

$token = ApiToken::generate(); // Returns 48-char alphanumeric string
```

### Best Practices
- Always return JSON responses for API endpoints
- Use appropriate HTTP status codes (200, 401, 400, 404, etc.)
- Validate request data before processing
- Use `#[PublicAccess]` attribute only for truly public endpoints (login, registration, etc.)
- Type-hint `Customer` parameter to automatically inject authenticated customer
- All API responses should follow consistent JSON structure
- Handle errors gracefully with descriptive error messages

## Avoid
- Superglobal arrays ($_GET, $_POST) in business logic
- SQL injection vulnerabilities
- Mixing HTML and PHP logic excessively
- Using deprecated PHP functions
- Hardcoding routes instead of using `path()` or `url()` functions
- Bypassing Symfony's service container

