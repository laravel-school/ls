# Laravel School

The personal publication of [Thouhedul Islam Suchi](https://tisuchi.com), at
[laravel-school.com](https://laravel-school.com).

Articles are markdown files. Committing one to `main` and pushing publishes it —
there is no CMS, no admin panel, and no step in between.

## Writing

Add a file under `prezet/content/posts/` (or `snippets/`), named with the next
number:

```markdown
---
title: What is a Data Transfer Object?
slug: what-is-a-data-transfer-object-158
date: 2026-08-09
category: Laravel
tags:
  - laravel
excerpt: A one-line summary, used in feeds and as the meta description.
---

The article.
```

`slug` is written by hand and never changes once published — see
[CONTEXT.md](CONTEXT.md).

Check it before pushing:

```bash
php artisan blog:validate
```

## Running locally

```bash
composer install
npm install
npm run dev        # vite, plus a server on :8000
```

No database is required, and no build step: with no generated Index present the
site reads the markdown directly, so edits appear on refresh.

## Commands

| Command | What it does |
| --- | --- |
| `php artisan blog:validate` | Checks every Document parses. Runs in CI on every push. |
| `php artisan blog:build` | Rebuilds the Index, sitemap, search index and social cards, and pre-renders every Document. Runs on the server at deploy. |

## How it fits together

- **[CONTEXT.md](CONTEXT.md)** — the vocabulary: Document, Frontmatter, Slug,
  Derived Artifact.
- **[docs/adr/](docs/adr)** — why it is built this way.
- **[docs/deploying.md](docs/deploying.md)** — the deploy script and what is
  deliberately missing from it.
- **`app/Content/`** — reading, indexing and rendering Documents.

Previously powered by [Prezet](https://prezet.com), whose shape this still owes
a great deal to.
