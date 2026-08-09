<?php

/**
 * Compares the site as it renders now against the baseline captured from the
 * previous implementation.
 *
 * Raw HTML is deliberately NOT compared. Two renderers can produce the same
 * page with different whitespace and attribute order, and diffing bytes would
 * bury a real regression under 157 false alarms. What is compared is what a
 * reader or a search engine can actually tell apart: the words on the page, the
 * links, the heading anchors people have deep-linked to, and the metadata.
 *
 * Usage: php scripts/parity-check.php [baseline-dir]
 */

$baselineDir = $argv[1] ?? __DIR__.'/../storage/app/parity/baseline';
$manifestPath = "$baselineDir/manifest.json";

if (! is_file($manifestPath)) {
    fwrite(STDERR, "No baseline at $manifestPath — run scripts/parity-snapshot.php on the previous implementation first.\n");
    exit(2);
}

$current = __DIR__.'/../storage/app/parity/current';

passthru(sprintf(
    '%s %s %s > /dev/null 2>&1',
    escapeshellarg(PHP_BINARY),
    escapeshellarg(__DIR__.'/parity-snapshot.php'),
    escapeshellarg($current),
), $status);

if ($status !== 0) {
    fwrite(STDERR, "Failed to snapshot the current implementation.\n");
    exit(2);
}

/** @var array<string, array<string, mixed>> $baseline */
$baseline = json_decode((string) file_get_contents($manifestPath), true);
/** @var array<string, array<string, mixed>> $now */
$now = json_decode((string) file_get_contents("$current/manifest.json"), true);

$compared = ['status', 'title', 'canonical', 'og_image', 'text', 'anchors', 'links', 'headings'];

/**
 * Differences we meant to introduce, each with the reason. Anything not listed
 * here is a regression. A check that is allowed to stay red stops being read,
 * so intentional changes get written down rather than tolerated.
 *
 * @var array<string, array<string, string>> $expected
 */
$expected = json_decode((string) file_get_contents(__DIR__.'/parity-expected.json'), true);

/**
 * Boilerplate deliberately removed from every page. Rather than excusing the
 * whole 'text' field site-wide — which would let a real content regression pass
 * unnoticed on all 159 pages — these fragments are stripped from the baseline
 * before comparing, so everything else stays strictly compared.
 *
 * @var array<int, string> $retired
 */
$retired = $expected['_retired_text'] ?? [];
unset($expected['_retired_text']);

$differences = [];
$accounted = [];

/** Same normalisation the snapshot uses: readable words only. */
$readableText = static function (string $html) use ($retired): string {
    $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/si', ' ', $html);
    $text = trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $html))));

    // Collapse again: removing a phrase leaves the spaces that surrounded it.
    return trim((string) preg_replace('/\s+/', ' ', str_replace($retired, '', $text)));
};

$fileFor = static fn (string $url): string => $url === '/' ? 'index' : trim(str_replace('/', '_', $url), '_');

foreach ($baseline as $url => $before) {
    if (! isset($now[$url])) {
        $differences[$url][] = 'URL is gone';

        continue;
    }

    foreach ($compared as $field) {
        if ($field === 'text' && $retired !== []) {
            $file = $fileFor($url).'.html';
            $same = $readableText((string) file_get_contents("$baselineDir/$file"))
                 === $readableText((string) file_get_contents("$current/$file"));

            if ($same) {
                continue;
            }
        } elseif ($before[$field] === $now[$url][$field]) {
            continue;
        }

        // '*' covers a change made deliberately across the whole site, so a
        // single decision does not have to be restated 141 times.
        $reason = $expected[$url][$field] ?? $expected['*'][$field] ?? null;

        if ($reason !== null) {
            $accounted[$url][$field] = $reason;

            continue;
        }

        $differences[$url][] = $field;
    }
}

foreach (array_diff(array_keys($now), array_keys($baseline)) as $added) {
    $differences[$added][] = 'URL is new';
}

$touched = array_unique([...array_keys($differences), ...array_keys($accounted)]);
$unchanged = count($baseline) - count($touched);

printf("%d URLs compared on: %s\n", count($baseline), implode(', ', $compared));
printf("  %d identical\n", $unchanged);
printf("  %d changed on purpose\n", count($accounted));
printf("  %d regressions\n\n", count($differences));

$byReason = [];

foreach ($accounted as $url => $fields) {
    foreach ($fields as $field => $reason) {
        $byReason[$reason][] = "$url [$field]";
    }
}

// Group by reason: one decision that changed 141 pages should read as one
// decision, not 141 lines of the same sentence.
foreach ($byReason as $reason => $urls) {
    printf("intended (%d URL%s)\n  %s\n  e.g. %s\n\n", count($urls), count($urls) === 1 ? '' : 's', wordwrap($reason, 94, "\n  "), $urls[0]);
}

if ($differences === []) {
    echo "\nParity reached: every difference is one we chose.\n";
    exit(0);
}

echo "\n";

foreach ($differences as $url => $fields) {
    printf("REGRESSION %-58s %s\n", substr($url, 0, 58), implode(', ', $fields));
}

exit(1);
