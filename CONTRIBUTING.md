# Contributing to River Flow

Thank you for your interest in contributing! This project targets modern PHP 8.5 with strict typing, PSR-12 code style, high test coverage, and strong static analysis.

## Getting Started
- PHP >= 8.5
- Composer 2.x

Install dependencies:
```bash
composer install
```

Run checks locally:
```bash
composer validate --no-check-publish
composer cs:lint
composer analyse
composer test
```

Optional coverage:
```bash
composer test:coverage
```

## Code Style
- PSR-12 enforced via PHP-CS-Fixer
- Lint: `composer cs:lint`
- Fix: `composer cs:fix`

## Static Analysis
- PHPStan at max level with strict rules and disallowed calls
- Run: `composer analyse`

## Automated Refactoring (Rector)
- Dry-run (no changes): `composer rector:check`
- Apply fixes: `composer rector:fix`

Note: The project targets PHP 8.5. If your local PHP is older, you can still run Rector by installing it with Composer using `--ignore-platform-req=php`, or upgrade your local PHP to 8.5. CI always runs on PHP 8.5.

## Testing
- Pest is the test framework
- Run: `composer test`
- Add tests for all new features and bug fixes

## Commit Messages
- Use Conventional Commits:
  - `feat: add ...`, `fix: correct ...`, `docs: update ...`, `test: add ...`, `chore: ...`, `refactor: ...`, etc.
- Scope is optional (e.g., `feat(parser): ...`).

## Branching & PRs
- Create feature branches off `main`
- Open a pull request with:
  - Description of the change and rationale
  - Tests that cover the change
  - Documentation updates if applicable (README or inline docs)
- Ensure all CI checks pass

## Security
- Do not open public issues for security vulnerabilities
- See SECURITY.md for our disclosure policy

## Releases & Versioning
- Semantic Versioning (SemVer)
- Breaking changes only in new major versions

## CI Notes
- CI runs on Linux/macOS/Windows with PHP 8.5
- Includes composer validate, code style lint, static analysis, and tests

Thanks again for helping improve River Flow!
