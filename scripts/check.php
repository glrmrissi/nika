<?php

declare(strict_types=1);

/**
 * Quality gate script for local development and CI.
 * Runs lints, validation, and dependency audit.
 *
 * Usage: php scripts/check.php [--no-audit]
 */

$skipAudit = in_array('--no-audit', $argv, true);

$checks = [
    'PHP syntax' => ['php', 'scripts/lint.php'],
    'Twig lint' => ['php', 'bin/console', 'lint:twig', 'templates'],
    'Container lint' => ['php', 'bin/console', 'lint:container'],
    'YAML lint' => ['php', 'bin/console', 'lint:yaml', 'config'],
    'Migrations up-to-date' => ['php', 'bin/console', 'doctrine:migrations:up-to-date'],
    'Composer validate' => ['composer', 'validate', '--strict'],
];

if (!$skipAudit) {
    $checks['Composer audit'] = ['composer', 'audit', '--locked'];
}

$failed = false;
foreach ($checks as $label => $command) {
    echo "\n==> {$label}\n";
    echo implode(' ', $command) . "\n";

    $exitCode = run($command);
    if ($exitCode !== 0) {
        echo "FAILED: {$label}\n";
        $failed = true;
    } else {
        echo "OK: {$label}\n";
    }
}

if ($failed) {
    echo "\nCheck failed.\n";
    exit(1);
}

echo "\nAll checks passed.\n";
exit(0);

function run(array $command): int
{
    $parts = array_map(fn (string $arg): string => escapeshellarg($arg), $command);
    $line = implode(' ', $parts);

    passthru($line, $exitCode);

    return $exitCode;
}
