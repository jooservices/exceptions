#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Statement-coverage gate: fails the build when coverage is below the required percentage.
 *
 * Usage: php tools/ensure-coverage.php <clover.xml> <required-percent>
 */

if ($argc !== 3) {
    fwrite(STDERR, "Usage: php tools/ensure-coverage.php <clover.xml> <required-percent>\n");

    exit(1);
}

[, $file, $required] = $argv;

if (!is_file($file)) {
    fwrite(STDERR, "Coverage file not found: {$file}\n");

    exit(1);
}

$xml = simplexml_load_file($file);

if ($xml === false || !isset($xml->project->metrics)) {
    fwrite(STDERR, "Could not parse coverage file: {$file}\n");

    exit(1);
}

$metrics = $xml->project->metrics;
$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

if ($statements === 0) {
    fwrite(STDERR, "No statements found in coverage report — did tests run?\n");

    exit(1);
}

$percent = ($covered / $statements) * 100;

printf(
    "Coverage: %d/%d statements (%.2f%%), required: %d%%\n",
    $covered,
    $statements,
    $percent,
    (int) $required,
);

if ($percent < (float) $required) {
    fwrite(STDERR, "Coverage below required threshold.\n");

    exit(1);
}
