---
name: fogito-framework-core-context
description: Provide minimal working context for Fogito shared PHP framework. Use when implementing, debugging, testing, or reviewing changes in framework/fogito-core, especially before broad repository exploration.
---

# Fogito shared PHP framework Context

## Start here

- Scope: `framework/fogito-core`.
- Stack: PHP/Composer library.
- Likely entry points: src/, tests/, example/
- Read order: local `AGENTS.md` if present; `README.md` headings first; open only the relevant section; then the manifest and only files matched by the task.

## Route dependencies

- Primary related repository: all PHP backends, but only when shared framework behavior is in scope.
- Shared or adjacent context: consumer repositories should normally be changed before the framework.
- Load another repository only when an API, shared contract, shell integration, or deployment boundary is part of the requested change.

## Commands

- Use the repository's documented test setup and target the narrowest suite under tests/.
- Confirm exact flags and environment requirements in the local manifest or deployment documentation.

## Context discipline

- Start with `rg` for the requested route, component, class, key, or error.
- Read the smallest relevant directory and nearby tests; do not crawl sibling repositories.
- Treat generated output, dependency directories, caches, and build artifacts as implementation details unless the task explicitly targets them.
- Preserve existing repository patterns and make the smallest complete change.

