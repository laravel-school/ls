---
status: accepted
---

# Torchlight for syntax highlighting, degrading to plain code on failure

Code blocks are highlighted server-side by **Torchlight**, a hosted API using the
VS Code engine, via `torchlight/torchlight-laravel`. The corpus is 549 fenced
blocks across 122 Documents — 271 `php`, 116 `sh`, 44 `bash`, 15 `diff` — and
highlighting them well matters more here than on most blogs.

This is a deliberate exception to ADR-0001. That ADR exists to reduce dependence
on a maintainer we do not control, and Torchlight is a SaaS we control even less.
It was accepted anyway because the output is HTML we keep and cache, the blast
radius of the service disappearing is code-block styling rather than content, and
the alternatives are worse: client-side highlight.js means a flash of unstyled
code and no diff or focus annotations, and no server-side PHP highlighter matches
VS Code grammars.

## Consequences

- **A Torchlight outage must not block a deploy.** Blocks fall back to plain
  `<pre><code>`, the Build logs a loud warning, and the deploy proceeds. This
  narrows ADR-0002's rule to: *fail on defects we can fix, degrade on failures we
  cannot.* A `blog:rebuild-cache` command exists to re-highlight afterwards,
  because the degraded output would otherwise sit in the cache indefinitely.
- **The cache is what keeps API usage sane.** Highlighting happens during the
  Build's cache pre-warm, so visitors never trigger API calls. Forge's default
  deploy is in-place, so `storage/` survives between deploys and blocks are
  highlighted once. **If deploys ever become atomic (Envoyer-style release
  directories), the framework cache must be a shared symlink** — otherwise every
  deploy re-highlights all 549 blocks and burns the monthly request quota.
- The site generates no revenue, so it uses Torchlight's **free tier**. That tier
  requires an attribution link, which the site must carry — a design constraint
  on the footer, not an optional courtesy. If the site ever earns money (ads,
  courses, affiliate links), the Business tier becomes required.
- Two themes are configured as an array (`min-light` / `night-owl`) so light and
  dark modes are both rendered and selected by CSS.
