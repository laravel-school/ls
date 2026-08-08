---
status: accepted
---

# Own the markdown pipeline instead of using a blogging package

The site ran on `benbjurstrom/prezet:1.0.0-rc5`, which Packagist marks as
**abandoned** — it never shipped a stable 1.0 and was last updated in May 2025.
Rather than migrate to its maintained successor, we are removing the blog engine
entirely and writing the pipeline ourselves on top of `league/commonmark`, which
`laravel/framework` already requires and therefore costs no new dependency.

## Considered Options

- **Migrate to `prezet/prezet:^1.4`** — the successor is stable, actively
  maintained (last release April 2026, 19k installs, Laravel 11–13), and would
  have eliminated the abandoned-dependency risk in a day or two. Rejected: the
  goal is not to be on a maintained package but to not depend on a single
  maintainer for the site's rendering, routing, and search at all.
- **Rebuild on a static site generator** (Astro, Eleventy, Hugo) — genuinely
  reduces bus factor by trading one maintainer for a large ecosystem, and would
  have removed the PHP server too. Rejected: a full rewrite of 155 Documents and
  1,103 lines of Blade into another templating language, for a stack the author
  does not work in daily.
- **Write our own markdown parser** — rejected as absurd; CommonMark's edge cases
  are years of work and 155 existing Documents depend on them.

## Consequences

Removing Prezet also removes five transitive dependencies: `archtechx/laravel-seo`,
`benbjurstrom/laravel-sitemap-lite` (a *second* single-maintainer package by the
same author), `spatie/laravel-package-tools`, `symfony/yaml`, and
`wendelladriel/laravel-validated-dto`. The orphaned `erusev/parsedown` goes too —
its last stable release was 2019.

We now own sitemap generation, OG image generation, search, and the document
model. The surface that must be reproduced to keep the existing Blade working is
small: twelve accessors, four view variables, and three route names.
