# WSEI E-commerce Wiki

Welcome to the WSEI E-commerce project documentation. This wiki provides comprehensive information about setting up, developing, and using the e-commerce application.

## Project Overview

WSEI E-commerce is a **Symfony 7.4** based e-commerce platform featuring:

- **REST API** for customer-facing operations (cart, orders, products, categories)
- **Token-based authentication** for API access
- **Admin Panel** for managing products, categories, customers, and orders
- **Stripe Payment Integration** for secure payments
- **Docker-based development environment**

## Wiki Sections

### 📦 [Setup](Setup.md)
Getting started with the project - Docker environment, installation, and initial configuration.

### 👨‍💻 [For Developers](For-Developers.md)
Architectural overview of controllers, entities, forms, framework components, repositories, and Twig extensions.

### 👥 [User Manual](User-manual.md)
Step-by-step guide for using the administration panel to manage products, categories, customers, orders, and administrator permissions.

## Technology Stack

| Component | Technology |
|-----------|------------|
| Framework | Symfony 7.4 |
| PHP Version | 8.1+ |
| Database | MySQL 8.0 |
| Payments | Stripe |
| API Docs | OpenAPI/Swagger |
| Testing | PHPUnit, Infection |
| Code Quality | PHPStan, ECS |
| Environment | Docker, Docker Compose |

## Quick Links

- [Makefile Commands Reference](Setup.md#makefile-commands)
- [Controllers Architecture](For-Developers.md#controllers)
- [EcommerceApi System](For-Developers.md#ecommerceapi-system)
- [Framework Components](For-Developers.md#framework)


