<?php

/**
 * Self-contained CI coverage gate: parses a Clover coverage report and fails
 * (non-zero exit) if the project-level line coverage percentage is below the
 * given floor. No third-party service (e.g. Codecov) involved.
 *
 * Usage: php scripts/coverage-check.php <path-to-clover.xml> <floor-percent>
 */
$path = $argv[1] ?? 'coverage.xml';
$floor = isset($argv[2]) ? (float) $argv[2] : 93.0;

if (! is_file($path)) {
    fwrite(STDERR, "Coverage file not found: {$path}\n");
    exit(1);
}

$xml = simplexml_load_file($path);

if ($xml === false) {
    fwrite(STDERR, "Failed to parse coverage file: {$path}\n");
    exit(1);
}

// The project-level totals are the <metrics> element that is a direct child
// of <project>, as opposed to the per-file/per-class <metrics> elements
// nested underneath it.
$metrics = $xml->xpath('/coverage/project/metrics');

if ($metrics === false || count($metrics) !== 1) {
    fwrite(STDERR, "Could not locate project-level <metrics> in {$path}\n");
    exit(1);
}

$attributes = $metrics[0]->attributes();
$covered = (int) $attributes->coveredstatements;
$total = (int) $attributes->statements;

if ($total === 0) {
    fwrite(STDERR, "Coverage file reports zero statements; refusing to pass.\n");
    exit(1);
}

$percentage = ($covered / $total) * 100;

printf("Line coverage: %.2f%% (%d/%d statements), floor %.2f%%\n", $percentage, $covered, $total, $floor);

if ($percentage < $floor) {
    fwrite(STDERR, sprintf("Coverage %.2f%% is below the required floor of %.2f%%\n", $percentage, $floor));
    exit(1);
}

exit(0);
