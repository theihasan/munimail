# AGENT.md - Munimail Development Guide

## Build/Test/Lint Commands
- **Test**: `composer test` (runs PHPUnit tests)
- **Single Test**: `php artisan test --filter=TestClassName` 
- **Lint**: `./vendor/bin/pint` (Laravel Pint code formatting)
- **Build**: `npm run build` (Vite frontend assets)
- **Dev**: `composer dev` (starts Laravel server, queue worker, logs, and Vite)
- **Queue**: `php artisan queue:work` (process background jobs)
- **SMTP Server**: `php artisan smtp:serve --port=2525` (start SMTP server)

## Architecture
- **Framework**: Laravel 12 with modular architecture using `nwidart/laravel-modules`
- **Modules**: Located in `/Modules/` directory (currently: SMTP module)
- **SMTP Server**: ReactPHP-based async SMTP server with TLS support
- **Storage**: Maildir format in `storage/app/maildir/`
- **Queue**: Laravel queue system for async email processing
- **Database**: Supports SQLite/MySQL/PostgreSQL

## Code Style & Conventions
- **PSR-4 Autoloading**: `Modules\{ModuleName}\{Namespace}`
- **Strict Types**: Always use `declare(strict_types=1);` at file start
- **PHP 8.2+ Features**: Use `match` expressions, property promotion, readonly properties
- **Naming**: PascalCase classes, camelCase methods, snake_case variables
- **Controllers**: Extend `App\Http\Controllers\Controller`
- **Commands**: Extend `Illuminate\Console\Command` with signature property
- **Exceptions**: Custom exceptions in `Modules\{Module}\Exceptions\`, use typed exceptions
- **Jobs**: Use `Modules\{Module}\Jobs\` with circuit breaker pattern and throttle exceptions
- **Performance**: Always aim for O(1) complexity, use efficient data structures
- **ReactPHP**: Use latest ReactPHP components for async operations
- **Error Handling**: Proper SMTP error codes, typed exceptions, graceful degradation
