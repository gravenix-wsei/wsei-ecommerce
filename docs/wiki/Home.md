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

### 👨‍💻 For Developers
- [Project Architecture](For-Developers.md) - Namespacing, directory structure, coding standards
- [Admin Settings Plugin System](Admin-Settings-Plugin-System.md) - Extending admin settings
- [Testing Guide](Testing-Guide.md) - Running unit and integration tests
- [Code Quality](Code-Quality.md) - PHPStan, ECS, and mutation testing

### 👥 For Users
- [Admin Panel](Admin-Panel.md) - Managing products, categories, customers, and orders
- [API Reference](API-Reference.md) - REST API endpoints and authentication

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
- [API Authentication](API-Reference.md#authentication)
- [Running Tests](Testing-Guide.md#running-tests)
- [Coding Standards](For-Developers.md#coding-standards)


