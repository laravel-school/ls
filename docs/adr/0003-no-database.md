---
status: accepted
---

# The site runs without a database

There is no database in development or production. Cache is file-backed, there
are no sessions, no queue, no migrations, and no `User` model.

This is less of a change than it sounds. The application had only Laravel's three
stock migrations (`users`, `cache`, `jobs`) and no tables of its own, and every
content route already ran with `StartSession`, `ShareErrorsFromSession`, and
`VerifyCsrfToken` stripped off — the public site was sessionless in practice. With
the Index held in memory (ADR-0004) and rendered HTML file-cached, nothing in the
request path can reach a database.

## Consequences

- The Forge deploy script drops `php artisan migrate --force`; CI stops needing a
  database service. A whole class of deploy failure disappears.
- **Anything requiring server-side state is foreclosed**: comments, newsletter
  signups stored locally, view counters, likes. Third-party embeds (Plausible,
  ConvertKit, Disqus) still work, because they hold the state elsewhere.
- Retrofitting SQLite later is cheap if that changes.
