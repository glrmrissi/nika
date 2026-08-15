<?php

declare(strict_types=1);

/**
 * Cross-platform PHP syntax linter.
 * Usage: php scripts/lint.php [path1 path2 ...]
 * Defaults to: src, migrations, public, tests (when present).
 */

$customPaths = array_slice($argv, 1);
$paths = $customPaths ?: ['src', 'migrations', 'public'];

if (is_dir('tests')) {
    $paths[] = 'tests';
}

$excludes = ['vendor', 'var', 'node_modules', 'public/bundles'];
$failed = [];
$checked = 0;

foreach ($paths as $path) {
    if (!is_dir($path) && !is_file($path)) {
        continue;
    }

    if (is_file($path)) {
        checkFile($path, $failed, $checked);
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        foreach ($excludes as $exclude) {
            if (str_contains($file->getPathname(), $exclude)) {
                continue 2;
            }
        }

        checkFile($file->getPathname(), $failed, $checked);
    }
}

if ($failed === []) {
    echo "OK: {$checked} PHP file(s) linted.\n";
    exit(0);
}

foreach ($failed as $file => $error) {
    echo "ERROR: {$file}\n{$error}\n";
}

$count = count($failed);
echo "FAILED: {$count} file(s) with syntax errors ({$checked} checked).\n";
exit(1);

function checkFile(string $file, array &$failed, int &$checked): void
{
    $checked++;
    $output = [];
    $exitCode = 0;
    exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        $failed[$file] = implode("\n", $output);
    }
}
