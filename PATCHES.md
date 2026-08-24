# Internal Vendor Patches — `roach-php/core`

This file documents all custom internal patches applied to the local `roach-php/core` package.

---

## [Patch-001] Explicit String Cast to `getUri()` and PHPStan Level 9 Fixes

- **Date**: 2026-08-11
- **Author**: Alex M. (`alexander.macenko@gmail.com`)
- **Commits**: `3a461d8731ced51f0001f42e6ba00cca3d6ff04b`, `43e7ada929a96b07742a9055e951ad67faca344a`, `711335dd20580dc687c34aa871aaeb8ed51b65f5`
- **Target Files**: HTTP Request/Response classes, PHPUnit test suites.
- **Problem Statement**: PHPStan level 9 strict type checking failed on URI handling and resource assertions; PHPUnit 10 compatibility warnings in DataProvider methods.
- **Fix Summary**:
  1. Added explicit string cast to `getUri()` and param docblocks for PHPStan level 9 compliance.
  2. Added `assertIsResource` assertions for PHPStan strict resource checks.
  3. Resolved CS-Fixer method ordering and PHPUnit 10 DataProvider compatibility in `ResponseTest`.
