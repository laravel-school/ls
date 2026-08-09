# Deploying

Publishing is one action: commit a Document to `main` and push. Everything else
is derived on the server.

## What happens on a push

1. GitHub Actions validates every Document, does a full build, and runs the
   tests. A malformed Document fails **here**, on the commit that introduced it.
2. If that passes and the branch is `main`, the workflow calls the Forge deploy
   webhook.
3. Forge pulls the new commit and runs the deploy script below.

## The Forge deploy script

Set this as the site's deploy script in Forge. Replace the PHP version if the
server runs something other than 8.2.

```bash
cd /home/forge/laravel-school.com

git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

# Rebuild everything derived from the Documents: the Index, the sitemap, the
# search index, the social cards, and the rendered-HTML cache. This is a full
# rebuild every time, which is what guarantees none of it can disagree with the
# markdown. If a Document is malformed the command fails, and because of the
# `set -e` Forge wraps the script in, the deploy stops with the previous
# version still serving.
$FORGE_PHP artisan blog:build

$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache

( flock -w 10 9 || exit 1
    echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
```

### What is deliberately absent

- **`php artisan migrate --force`.** There is no database. See
  [ADR-0003](adr/0003-no-database.md).
- **Anything that commits generated files.** The Index, sitemap, search index
  and social cards are written on the server and are gitignored. See
  [ADR-0002](adr/0002-derived-artifacts-are-built-at-deploy.md).

### Cache and atomic deploys

`blog:build` warms a render cache in `storage/framework/cache`. Forge's default
deploy is in place — the same directory, updated by `git pull` — so that cache
survives between deploys and pages are rendered once.

**If deploys ever become atomic** (Envoyer-style, a new release directory each
time), `storage` must be a shared symlink. Otherwise every deploy starts with an
empty cache and re-renders all 157 Documents, and once Torchlight is wired up,
re-highlights all 549 code blocks against a metered API.

## Environment

The server needs `APP_URL=https://laravel-school.com`. The sitemap and every
canonical URL are built from it, and a wrong value here is what once published
157 `http://localhost` URLs to search engines.

No database credentials, queue connection, or session store are needed.

## Social cards

Cards are drawn with GD from a font file at `resources/fonts/Inter-SemiBold.ttf`.
If that file is absent the build logs a warning and skips them — Documents
without an image of their own simply get no `og:image`, exactly as before. The
build does not fail.
