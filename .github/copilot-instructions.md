# GitHub Copilot Instructions - WSEI Monorepo
- Using deprecated language/framework functions
- Mixing HTML and business logic excessively
- Hardcoded credentials
- SQL injection vulnerabilities
- Global variables
## Avoid

- Focus on code implementation, not documentation files
- Only create technical documentation when specifically asked
- **NEVER create summary or implementation documentation files** (e.g., SUMMARY.md, IMPLEMENTATION.md, CHECKLIST.md, etc.) unless explicitly requested by the user
#### Documentation

### Important Project Rules

- Use meaningful test names that describe behavior
- Test edge cases and error conditions
- Write unit tests for business logic
#### Testing

- Minimize database calls
- Lazy load resources when possible
- Optimize database queries (avoid N+1 problems)
- Implement caching where appropriate
#### Performance

- Never expose sensitive information in errors
- Show user-friendly error messages
- Log errors appropriately
- Use try-catch blocks for expected exceptions
#### Error Handling

### Best Practices

- Document API endpoints and their parameters
- Keep comments up-to-date with code changes
- Comment complex business logic
- Write appropriate doc blocks for classes and methods
#### Comments and Documentation

- Follow accessibility best practices (ARIA labels, semantic HTML)
- Ensure responsive design
- Implement progressive enhancement
- Use modern ES6+ syntax
- Keep JavaScript modular and maintainable
#### Frontend

- Follow naming conventions: snake_case for tables and columns
- Use transactions for related operations
- Index foreign keys and frequently queried columns
- Prefer migrations for schema changes
- Use framework's ORM/query builder
#### Database

- Use environment variables for sensitive configuration
- Validate and escape output to prevent XSS
- Hash passwords using framework's recommended methods
- Implement CSRF protection for forms
- Use prepared statements for database queries
- Always sanitize user input
#### Security

- Separate concerns: controllers, models, services, repositories
- Use dependency injection over static calls
- Keep controllers thin, move business logic to services
#### Code Organization

- Follow camelCase for methods and variables, PascalCase for classes
- Use meaningful variable and function names
- Prefer type hints for parameters and return types
- Use strict types: `declare(strict_types=1);`
- Follow PSR-12 coding standards
- Use PHP 8.0+ features where applicable
#### PHP (All PHP Projects)

### Coding Standards

- Use `make` commands when available for common tasks
- Never run framework commands directly on the host machine
- **Always run project commands inside their respective Docker containers**
### Docker Environment

## Global Guidelines

- *(more projects will be added)*
- **ecommerce/** - Symfony 7.4 e-commerce application (see `ecommerce/.github/copilot-instructions.md`)
### Projects

This is a **monorepo** containing multiple independent projects. Each project has its own specific instructions.
## Monorepo Structure


