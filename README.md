# River Flow for PHP 8.5

[![CI](https://github.com/denprog/river-flow/actions/workflows/ci.yml/badge.svg)](https://github.com/denprog/river-flow/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Modern, strictly-typed PHP 8.5 library scaffold with testing, static analysis, and CI/CD baked in.

## Requirements
- PHP >= 8.5
- Composer 2

## Installation
```bash
composer require denprog/river-flow
```

## Usage
The public API will be documented here. For now, this package is being scaffolded for PHP 8.5 with Pest and PHPStan. Check back soon for API details.

## Development
Clone the repository and install dependencies:
```bash
composer install
```

Run the checks:
```bash
composer test           # run tests (Pest)
composer analyse        # static analysis (PHPStan)
composer cs:lint        # code style (PHP-CS-Fixer dry-run)
composer cs:fix         # fix code style issues
composer test:coverage  # run tests with coverage
```

## Security
Please review SECURITY.md for our vulnerability disclosure policy and how to report issues securely.

## Contributing
See CONTRIBUTING.md for guidelines on setting up the environment, coding standards (PSR-12), commit message conventions, and pull request process.

## Code of Conduct
This project adheres to the Contributor Covenant. By participating, you are expected to uphold this code. See CODE_OF_CONDUCT.md.

## Versioning
This project follows Semantic Versioning (SemVer). Breaking changes will only occur in a new major version.

## License
Licensed under the MIT License. See the LICENSE file for details.
