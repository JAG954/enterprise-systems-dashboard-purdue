# Contributing

Thanks for taking the time to review or improve this project.

## Project Scope

This repository is preserved as a Purdue IE332 portfolio artifact for a PHP and MySQL supply chain analytics dashboard. Contributions should keep that scope intact: improve clarity, reproducibility, security, or maintainability without turning the project into a different application.

## Development Setup

1. Install PHP 8.x with the `mysqli` extension.
2. Install MySQL 8.x or a compatible local database.
3. Copy `www/config.local.example.php` to `www/config.local.php`.
4. Fill in local database credentials in `www/config.local.php`, or use the documented `DB_*` environment variables.
5. Start the local server:

```bash
php -S localhost:8000 -t www
```

## Before Opening a Pull Request

- Keep secrets, database dumps with private data, and machine-specific files out of git.
- Run PHP syntax checks if PHP is available locally:

```bash
find www -name '*.php' -print0 | xargs -0 -n1 php -l
```

- Run a quick secret scan over the working tree:

```bash
rg -n "mydb\\.|password\\s*=\\s*['\\\"]|api[_-]?key|secret|token" .
```

- Update documentation when setup, behavior, or repository structure changes.
- Include screenshots when changing visible dashboard behavior.

## Pull Request Expectations

- Describe the problem and the change.
- Mention any database schema assumptions.
- List verification steps performed.
- Keep portfolio-specific context clear for reviewers who are not running the full database locally.
