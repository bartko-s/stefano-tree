# CLAUDE.md

## Running tests
- Tests run inside a docker php container (`php8.2`–`php8.5` = PHP 8.2–8.5) via composer scripts (see "scripts" in composer.json):
  - `./run.sh php8.2 composer test` - full matrix (all 6 combinations)
  - `./run.sh php8.5 composer test-pgsql-pdo` - single combination; naming: `test-{mysql|pgsql}-{pdo|laminas-db|doctrine-dbal}`
- tests/bootstrap.php throws unless the `DB` and `ADAPTER` env vars are set (the composer scripts export them) -
  running phpunit directly without them fails, even for the unit suite.
- Code style: `./run.sh composer cs-check` / `./run.sh composer cs-fix` (php-cs-fixer, config: .php-cs-fixer.php).
- DB connection defaults: tests/testConfig.php; override locally via tests/testConfig.local.php (copy from .dist)


## Documentation
- All documentation (README, changelog, docblocks, wiki) must be written in English.

## Commit messages
- Use prefixes like `ADDED - `, `FIX -`, `CHANGE - `, `UPDATE - ` (see git history).