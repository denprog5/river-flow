# Security Policy

## Supported Versions
Security updates are provided for the latest released minor version of this package.

## Reporting a Vulnerability
- Please do NOT open public issues for security vulnerabilities.
- Use GitHub Security Advisories (preferred) to report vulnerabilities privately to the maintainers.
- If Security Advisories are unavailable, contact the maintainers privately and provide:
  - A description of the vulnerability and affected versions
  - Steps to reproduce
  - Impact assessment
  - Suggested remediation if known

We will acknowledge your report within 72 hours and provide a timeline for a fix.

## Safe Coding Guidelines
This project enforces strong static analysis and forbids dangerous calls such as `eval()`, `unserialize()`, and shell execution functions. Do not introduce such patterns in code or tests.
