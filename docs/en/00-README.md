# Edu-Bridge Documentation

Complete documentation of the project (Laravel backend + Flutter app) for handover, sale, or onboarding a new developer.

**Last reviewed:** 2026-10-05 | Arabic version: [`../ar/`](../ar/00-README.md)

## Where do I start?

| You are... | Read in this order |
|---|---|
| **Buyer / evaluator** | [01 Overview](01-product-overview.md) -> [08 Project status](08-project-status.md) -> [09 Roadmap](09-roadmap.md) -> [10 Handover checklist](10-handover-checklist.md) |
| **New developer** | [02 Architecture](02-architecture.md) -> [06 Setup & deploy](06-setup-and-deploy.md) -> [05 Key flows](05-key-flows.md) -> [03 Database](03-database.md) -> [04 API reference](04-api-reference.md) |
| **Security reviewer** | [07 Security design](07-security.md) -> [08 Project status](08-project-status.md) |

## Index

| # | File | Contents |
|---|---|---|
| 01 | [product-overview](01-product-overview.md) | What the product is, roles, features |
| 02 | [architecture](02-architecture.md) | Architecture, layers, external services, Flutter app |
| 03 | [database](03-database.md) | Data dictionary (65 tables) + ERD *(auto-generated)* |
| 04 | [api-reference](04-api-reference.md) | 329 API endpoints grouped by role *(auto-generated)* |
| 05 | [key-flows](05-key-flows.md) | Diagrams: registration, login, attendance, warnings, leave, chat |
| 06 | [setup-and-deploy](06-setup-and-deploy.md) | Install, environment variables, bot, Flutter, deployment |
| 07 | [security](07-security.md) | Existing security controls and developer guidance |
| 08 | [project-status](08-project-status.md) | The honest state: vulnerabilities, bugs, technical debt |
| 09 | [roadmap](09-roadmap.md) | What to do, in order |
| 10 | [handover-checklist](10-handover-checklist.md) | Handover checklist, licenses, ownership |

## Regenerating the auto-generated files

`03-database.md` and `04-api-reference.md` are generated from code, not written by hand:

- **Database:** run `php artisan migrate` on an empty database, then read `information_schema`.
- **API:** from `php artisan route:list --json`.

Regenerate them whenever the schema or routes change so the documentation never goes stale.

## Limits of this documentation (stated plainly)

- Prepared by static code review, without penetration or load testing.
- Not every controller was read line by line; section 7 of [`08`](08-project-status.md) lists what was not reviewed.
- The Flutter code (~73K lines) was reviewed at the structure and services level, not screen by screen.
- Some Arabic-only details (UI strings, code comments) are intentionally left untranslated.
