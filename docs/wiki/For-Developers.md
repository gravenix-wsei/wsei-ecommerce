# For Developers

This guide provides an architectural overview of the WSEI E-commerce application for developers who have completed the initial setup.

## Controllers

### Admin Controllers

Located in `Controller/Admin/`, these handle the administration panel:

- **Standard CRUD controllers**: `ProductController`, `CategoryController`, `CustomerController`, `OrderController`, `AddressController`
- **Route pattern**: `/admin/{resource}` with names like `admin.product.index`, `admin.product.new`
- **Security**: Require `#[IsGranted('ROLE_ADMIN.*')]` - specific roles per controller (e.g., `ROLE_ADMIN.PRODUCT`)
- **Return**: Rendered Twig templates for admin panel views

#### Settings Section

`SettingsController` displays a grid of available settings. Individual setting controllers are in `Controller/Admin/Settings/{SettingName}/` and implement `EcommerceSettingsInterface`. Settings are auto-discovered via service tagging.

### EcommerceApi Controllers

Located in `Controller/EcommerceApi/V1/{Resource}/`:

- **Route pattern**: `/ecommerce/api/v1/{resource}`
- **Return**: Custom response DTOs extending `EcommerceResponse`
- **Authentication**: Token-based via `wsei-ecommerce-token` header (handled automatically)
- **Documentation**: Use OpenAPI attributes (`#[OA\Post]`, `#[OA\Get]`, etc.)

## EcommerceApi System

### Authentication

**Token-based authentication** using the `wsei-ecommerce-token` header.

- **ApiTokenAuthenticationSubscriber**: Validates tokens on each request to `/ecommerce/api/v1/*` endpoints
- **Checks for**: `#[PublicAccess]` attribute - if present, skips authentication
- **On success**: Stores authenticated `Customer` in request attributes
- **On failure**: Throws `UnauthorizedException` (401)

### PublicAccess Attribute

Mark endpoints that don't require authentication:

```php
#[PublicAccess]
#[Route('/login', methods: ['POST'])]
```

Without this attribute, the endpoint requires a valid token.

### Customer Dependency Injection

**CustomerValueResolver** automatically injects the authenticated `Customer` entity into controller methods by:
1. Checking if parameter type is `Customer`
2. Extracting authenticated customer from request attributes
3. Returning customer instance for injection

Use in controllers:
```php
public function show(Customer $customer): CartResponse
```

### Custom Exception Handling

**HttpExceptionSubscriber** catches exceptions and formats responses.

Available exceptions in `EcommerceApi/Exception/Http/`:
- `BadRequestException` - 400
- `NotFoundException` - 404
- `InvalidCredentialsException` - 401
- `UnauthorizedException` - 401

All extend `HttpException` and return JSON:
```json
{
  "error": "Error Name",
  "message": "Error message",
  "errorCode": "ERROR_CODE",
  "apiDescription": "Error"
}
```

### Response Classes

All responses extend `EcommerceResponse` which:
- Auto-sets `Content-Type: application/json`
- Formats data via `formatData()` method
- Adds `apiDescription` field

Implement two methods:
- `formatData(): array` - Returns response data
- `getApiDescription(): string` - Returns API description

### Payload Classes

Located in `EcommerceApi/Payload/`, these use:
- Readonly constructor properties
- Symfony validation constraints (`#[Assert\NotBlank]`, `#[Assert\Positive]`, etc.)
- OpenAPI schema documentation (`#[OA\Schema]`)
- `#[MapRequestPayload]` attribute in controllers for auto-validation

### OpenAPI Documentation

- Controller level: `#[OA\Tag(name: 'Resource')]`
- Endpoint level: `#[OA\Post(...)]`, `#[OA\Get(...)]`
- Security annotation: `security: [['ApiToken' => []]]` for protected endpoints
- Schema references: `ref: '#/components/schemas/SchemaName'`
- Generate spec: `vendor/bin/openapi src/Controller/EcommerceApi -o public/openapi.json`

## Entities

All entities in `Entity/` directory use Doctrine ORM with PHP attributes:

| Entity | Purpose |
|--------|---------|
| `Product` | Store products with prices, stock, category |
| `Category` | Product categorization |
| `Customer` | Customer accounts (separate from admin users) |
| `User` | Admin panel users |
| `Cart` | Shopping cart for customers |
| `CartItem` | Individual items in cart |
| `Order` | Placed customer orders |
| `OrderItem` | Line items in orders |
| `OrderAddress` | Shipping address snapshot for orders |
| `Address` | Customer saved addresses |
| `ApiToken` | Authentication tokens for API (one per customer) |
| `PaymentSession` | Stripe payment session tracking |

## Forms

Located in `Form/Admin/`, standard Symfony FormType classes for admin panel:

- `ProductType` - Product creation/editing
- `CategoryType` - Category management
- `CustomerType` - Customer account management
- `AddressType` - Address forms
- `OrderStatusType` - Order status updates
- `AdminUserType` - Admin user management

Forms use Symfony's form component with CSRF protection enabled.

## Framework

### Admin Settings System

**How it works:**
1. Controllers implement `EcommerceSettingsInterface` with methods: `getName()`, `getIcon()`, `getDescription()`, `getPosition()`, `getPathEntrypointName()`
2. Auto-tagged with `wsei_ecommerce.admin.setting` via `_instanceof` in `services.yaml`
3. `SettingsProvider` collects all tagged services and creates `SettingItem` DTOs
4. Main `SettingsController` displays grid of setting cards sorted by position/name


### Checkout Classes

**Cart Management** (`Framework/Checkout/Cart/`):
- `CartServiceInterface` - Main cart operations (create, add items, remove, update quantity)
- `CartService` - Implementation handling cart and cart item logic

**Order Management** (`Framework/Checkout/Order/`):
- `OrderServiceInterface` - Order creation and management
- `OrderService` - Implementation for placing orders from carts
- `OrderStatus` - Enum: `NEW`, `PENDING_PAYMENT`, `PAID`, `SENT`, `DELIVERED`, `CANCELLED`
- `OrderStatusTransitionInterface` - Validates allowed status transitions
- `OrderStatusTransition` - Implementation with transition rules

### Payment Handler

`PaymentServiceInterface` in `Framework/Payment/`:
- `pay(Order $order, string $returnUrl): PaymentResult` - Initiates payment
- `verify(string $token): PaymentVerificationResult` - Verifies payment callback

**Implementation**: `Stripe/StripePaymentService` handles Stripe checkout sessions.

### AdminRole (RBAC)

`Framework/Security/AdminRole` is an enum defining all admin roles:
- `ROLE_ADMIN` - Base admin role
- `ROLE_ADMIN.PRODUCT` - Product management
- `ROLE_ADMIN.CATEGORY` - Category management
- `ROLE_ADMIN.CUSTOMER` - Customer management
- `ROLE_ADMIN.ORDER` - Order management
- `ROLE_ADMIN.CONFIG` - Configuration/settings access
- `ROLE_SUPER_ADMIN` - Full access

Each enum case has `getLabel()` and `getDescription()` methods.

**Usage**: Controllers use `#[IsGranted('ROLE_ADMIN.PRODUCT')]` to restrict access. Role hierarchy is configured in `config/packages/security.yaml`.

## Repositories

Standard Doctrine repositories in `Repository/` directory extending `ServiceEntityRepository`:

`AddressRepository`, `ApiTokenRepository`, `CartItemRepository`, `CartRepository`, `CategoryRepository`, `CustomerRepository`, `OrderAddressRepository`, `OrderItemRepository`, `OrderRepository`, `PaymentSessionRepository`, `ProductRepository`, `UserRepository`

Each repository handles database queries for its entity using Doctrine QueryBuilder and DQL.

## Twig Extensions

Located in `Twig/` directory:

### SvgExtension

Provides `svg()` function to load SVG files from `public/img/icons/`:

```twig
{{ svg('img/icons/plus.svg', {class: 'icon-class'}) }}
```

Reads file content and optionally adds HTML attributes to the `<svg>` tag.

### OrderStatusExtension

Provides two filters:
- `order_status_badge` - Returns CSS class for status badge styling
- `order_status_label` - Returns human-readable status label

**Creating new extensions:**
1. Extend `AbstractExtension`
2. Implement `getFunctions()` for functions or `getFilters()` for filters
3. Return array of `TwigFunction` or `TwigFilter` instances
4. Auto-registered via autowiring

## Utility

### Defaults.php

Contains project-wide constant values:

```php
class Defaults
{
    public const DEFAULT_DATE_FORMAT = 'Y-m-d H:i:s';
}
```

Used throughout the application for consistent date formatting in API responses and display.


