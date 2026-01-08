# Setup

This guide covers the complete setup process for the WSEI E-commerce project.

## Prerequisites

Before starting, ensure you have the following installed:

- **Docker** (20.10+)
- **Docker Compose** (2.0+)
- **Make** (GNU Make)
- **Git**

## Quick Start

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd wsei-ecommerce
   ```

2. **Initialize the environment**
   ```bash
   make init
   ```

This single command will:
- Copy `.env.example` to `.env` (if not exists)
- Build Docker containers
- Start the application

3. **Install dependencies and run migrations**
   ```bash
   make php-exec CMD="composer install"
   make php-exec CMD="bin/console doctrine:migrations:migrate --no-interaction"
   ```

4. **Access the application**
   - **Admin Panel**: http://localhost:8080/admin
   - **API Base URL**: http://localhost:8080/ecommerce/api/v1/

## Default Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin` | `password` |

## Environment Configuration

The project uses environment variables for configuration. Copy `.env.example` to `.env` and modify as needed:

```bash
# Database Configuration
MYSQL_DATABASE=ecommerce
MYSQL_USER=ecommerce
MYSQL_PASSWORD=secret
MYSQL_ROOT_PASSWORD=root

# Stripe Payment Configuration
STRIPE_SECRET_KEY=sk_test_your_key_here
STRIPE_PUBLISHABLE_KEY=pk_test_your_key_here
```

> ⚠️ **Security Note**: Never commit real API keys to version control. Use `.env.local` for sensitive configuration.

## Docker Architecture

The project uses the following Docker containers:

| Container | Service | Port |
|-----------|---------|------|
| `php` | PHP-FPM 8.1 | - |
| `nginx` | Nginx Web Server | 8080 |
| `mysql` | MySQL 8.0 | 3306 |

## Makefile Commands

All commands should be executed from the project root directory. Otherwise, makefile won't work properly. If you want to run makefile in different directory, you should provide `-C` option with relative path to the root of the project.

### Container Management

| Command | Description |
|---------|-------------|
| `make init` | Initialize environment: create .env, build and start containers |
| `make up` | Create and start containers |
| `make down` | Stop and remove containers |
| `make start` | Start containers |
| `make stop` | Stop containers |
| `make restart` | Restart containers |
| `make build` | Build or rebuild services |
| `make logs` | Preview container logs (follow mode) |
| `make shell` | Open shell in PHP container |

### PHP Command Execution

Execute any command inside the PHP container:

```bash
make php-exec CMD="<command>"
```

**Examples:**

```bash
# Symfony console commands
make php-exec CMD="bin/console cache:clear"
make php-exec CMD="bin/console doctrine:migrations:migrate"
make php-exec CMD="bin/console make:entity"

# Composer commands
make php-exec CMD="composer install"
make php-exec CMD="composer require package-name"
make php-exec CMD="composer update"

# Code quality tools
make php-exec CMD="composer phpstan"
make php-exec CMD="composer ecs"

# Running tests
make php-exec CMD="composer test"

# Chain multiple commands
make php-exec CMD="composer install && bin/console cache:clear"
```

### Testing Commands

| Command | Description | Report Location |
|---------|-------------|-----------------|
| `make php-test-init` | Create test database and run migrations | N/A |
| `make php-run-tests` | Run PHP unit tests with coverage report | Terminal output |
| `make php-coverage-check` | Check coverage for changed files | `ecommerce/phpunit-coverage/diff-coverage.html` |

### Code Quality

| Command | Description | Report Location |
|---------|-------------|-----------------|
| `make phpstan` | Run PHPStan static analysis | Terminal output |
| `make ecs` | Run Easy Coding Standard check | Terminal output |
| `make ecs-fix` | Run ECS and fix violations automatically | Terminal output |
| `make phpmetrics` | Generate PHP metrics report | `ecommerce/phpmetrics/index.html` |
| `make infection` | Run PHP tests mutation report | `ecommerce/infection-coverage/infection.html` |



## Database Setup

### Development Database

The development database is automatically created when containers start. Run migrations:

```bash
make php-exec CMD="bin/console doctrine:migrations:migrate --no-interaction"
```

### Test Database

Initialize the test database before running integration tests:

```bash
make php-test-init
```

To recreate the test database (drop and create fresh):

```bash
make php-test-init CLEAR_EXISTING=true
```

### Running Migrations

```bash
# Run pending migrations
make php-exec CMD="bin/console doctrine:migrations:migrate"

# Create a new migration
make php-exec CMD="bin/console make:migration"

# Check migration status
make php-exec CMD="bin/console doctrine:migrations:status"

# Rollback last migration
make php-exec CMD="bin/console doctrine:migrations:migrate prev"
```

## Troubleshooting

### Container Issues

**Containers won't start:**
```bash
make down
make build
make up
```

**Permission issues:**
```bash
# Fix permissions on var directory
make php-exec CMD="chmod -R 777 var"
```

### Database Issues

**Connection refused:**
- Ensure MySQL container is running: `docker compose ps`
- Wait for MySQL to fully initialize (check logs: `make logs`)

**Migration errors:**
```bash
# Validate schema
make php-exec CMD="bin/console doctrine:schema:validate"

# Force sync (development only!)
make php-exec CMD="bin/console doctrine:schema:update --force"
```

### Cache Issues

```bash
# Clear all caches
make php-exec CMD="bin/console cache:clear"

# Clear specific cache pools
make php-exec CMD="bin/console cache:pool:clear cache.app"
```

## Development Workflow

1. **Start your day:**
   ```bash
   make up
   ```

2. **Work on features:**
   ```bash
   make php-exec CMD="bin/console make:entity"
   make php-exec CMD="bin/console make:migration"
   make php-exec CMD="bin/console doctrine:migrations:migrate"
   ```

3. **Run tests before committing:**
   ```bash
   make ecs
   make phpstan
   make php-run-tests
   ```

4. **End your day:**
   ```bash
   make stop
   ```

---

**Next:** [For Developers →](For-Developers.md)

