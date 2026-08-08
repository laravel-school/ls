<?php

/**
 * Captures a snapshot of every Document page for the rebuild's parity harness.
 *
 * Requests are dispatched through the HTTP kernel rather than over the network,
 * so status codes are the application's own and not whatever the dev server
 * decides to report.
 *
 * Usage: php scripts/parity-snapshot.php [output-dir]
 * Default output: storage/app/parity/baseline
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);

$out = $argv[1] ?? __DIR__.'/../storage/app/parity/baseline';

/**
 * Every URL the site is expected to serve: the two feeds, every Post and
 * Snippet by its Slug, and the Pages by their filepath-derived slugs.
 */
$urls = ['/', '/posts'];

foreach (['posts', 'snippets'] as $dir) {
    foreach (glob(__DIR__."/../prezet/content/$dir/*.md") as $file) {
        if (preg_match('/^slug:\s*(.+)$/m', file_get_contents($file), $m)) {
            $urls[] = '/posts/'.trim($m[1], " \t\"'");
        }
    }
}

foreach (glob(__DIR__.'/../prezet/content/about/*.md') as $file) {
    $urls[] = '/posts/about/'.basename($file, '.md');
}

@mkdir($out, 0777, true);

$manifest = [];

foreach ($urls as $url) {
    $response = $kernel->handle(Request::create($url, 'GET'));
    $body = $response->getContent();
    $name = $url === '/' ? 'index' : trim(str_replace('/', '_', $url), '_');

    file_put_contents("$out/$name.html", $body);

    $manifest[$url] = [
        'status' => $response->getStatusCode(),
        'location' => $response->headers->get('Location'),
        'bytes' => strlen($body),
        'sha' => hash('sha256', $body),
        'text' => hash('sha256', normalise($body)),
        'title' => extract_one('/<title>(.*?)<\/title>/s', $body),
        'canonical' => extract_one('/<link[^>]+rel="canonical"[^>]+href="([^"]+)"/i', $body),
        'og_image' => extract_one('/<meta[^>]+property="og:image"[^>]+content="([^"]+)"/i', $body),
        'anchors' => collect_all('/<[^>]+id="(content-[^"]+)"/i', $body),
        'links' => collect_all('/<a[^>]+href="(\/[^"]*)"/i', $body),
        'headings' => collect_all('/<h([1-6])[^>]*>\s*(.*?)\s*<\/h\1>/s', $body, 2),
    ];
}

ksort($manifest);
file_put_contents("$out/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$codes = array_count_values(array_column($manifest, 'status'));
ksort($codes);

echo 'Snapshotted '.count($manifest)." URLs to $out\n";
foreach ($codes as $code => $n) {
    echo "  HTTP $code: $n\n";
}

/** Strip markup and collapse whitespace, so only the readable text remains. */
function normalise(string $html): string
{
    $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/si', ' ', $html);

    return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html))));
}

function extract_one(string $pattern, string $body): ?string
{
    return preg_match($pattern, $body, $m) ? trim($m[1]) : null;
}

function collect_all(string $pattern, string $body, int $group = 1): array
{
    preg_match_all($pattern, $body, $m);

    return array_values(array_unique(array_map('trim', $m[$group] ?? [])));
}
