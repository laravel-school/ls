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

$differences = [];
$accounted = [];

foreach ($baseline as $url => $before) {
    if (! isset($now[$url])) {
        $differences[$url][] = 'URL is gone';

        continue;
    }

    foreach ($compared as $field) {
        if ($before[$field] === $now[$url][$field]) {
            continue;
        }

        if (isset($expected[$url][$field])) {
            $accounted[$url][] = $field;

            continue;
        }

        $differences[$url][] = $field;
    }
}

foreach (array_diff(array_keys($now), array_keys($baseline)) as $added) {
    $differences[$added][] = 'URL is new';
}

$unchanged = count($baseline) - count($differences) - count($accounted);

printf("%d URLs compared on: %s\n", count($baseline), implode(', ', $compared));
printf("  %d identical\n", $unchanged);
printf("  %d changed on purpose\n", count($accounted));
printf("  %d regressions\n\n", count($differences));

foreach ($accounted as $url => $fields) {
    foreach ($fields as $field) {
        printf("intended  %s [%s]\n          %s\n", $url, $field, wordwrap($expected[$url][$field], 96, "\n          "));
    }
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
