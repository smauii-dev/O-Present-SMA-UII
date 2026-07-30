#!/usr/bin/env php
<?php

/**
 * Test Runner Script for O-Present SMA UII
 *
 * Runs all tests (PHPUnit + Headless Chrome)
 * Usage: php tests/run-tests.php [--unit] [--feature] [--e2e] [--all]
 */

declare(strict_types=1);

// Check if running in CI/CD
$ciMode = getenv('CI') !== false;
$headless = true; // Always headless on server

echo "===========================================\n";
echo "O-Present SMA UII - Test Runner\n";
echo "===========================================\n\n";

// Parse command line arguments
$options = getopt('', ['unit', 'feature', 'e2e', 'all', 'help']);

$runUnit = isset($options['unit']) || isset($options['all']) || empty($options);
$runFeature = isset($options['feature']) || isset($options['all']) || empty($options);
$runE2E = isset($options['e2e']) || isset($options['all']) || empty($options);

if (isset($options['help'])) {
    echo "Usage: php tests/run-tests.php [options]\n\n";
    echo "Options:\n";
    echo "  --unit     Run unit tests only\n";
    echo "  --feature  Run feature tests only\n";
    echo "  --e2e      Run end-to-end tests only\n";
    echo "  --all      Run all tests (default)\n";
    echo "  --help     Show this help\n";
    exit(0);
}

// Check prerequisites
echo "Checking prerequisites...\n";

// Check PHP
if (!file_exists('/usr/bin/php')) {
    echo "❌ PHP not found!\n";
    exit(1);
}
echo "✅ PHP: " . PHP_VERSION . "\n";

// Check Chrome/Chromium
$chromePaths = ['/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser'];
$chromePath = null;
foreach ($chromePaths as $path) {
    if (file_exists($path)) {
        $chromePath = $path;
        break;
    }
}

if (!$chromePath) {
    echo "⚠️  Chrome/Chromium not found. E2E tests will be skipped.\n";
    $runE2E = false;
} else {
    echo "✅ Chrome: $chromePath\n";
}

// Check Composer dependencies
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "❌ Composer dependencies not installed. Run: composer install\n";
    exit(1);
}
echo "✅ Composer autoload found\n\n";

// Run tests
$exitCode = 0;

if ($runUnit || $runFeature) {
    echo "Running PHPUnit tests...\n";
    echo "-------------------------------------------\n";

    $phpunitConfig = __DIR__ . '/../phpunit.xml.dist';
    if (!file_exists($phpunitConfig)) {
        $phpunitConfig = __DIR__ . '/../phpunit.xml';
    }

    $command = sprintf(
        'cd %s && vendor/bin/phpunit --configuration %s --colors=always %s 2>&1',
        escapeshellarg(dirname(__DIR__)),
        escapeshellarg($phpunitConfig),
        $runUnit && !$runFeature ? '--testsuite Unit' : ''
    );

    passthru($command, $phpunitExitCode);

    if ($phpunitExitCode !== 0) {
        $exitCode = $phpunitExitCode;
        echo "\n❌ PHPUnit tests failed!\n\n";
    } else {
        echo "\n✅ PHPUnit tests passed!\n\n";
    }
}

if ($runE2E && $chromePath) {
    echo "Running End-to-End tests with Headless Chrome...\n";
    echo "-------------------------------------------\n";

    // Run E2E test script
    $e2eScript = __DIR__ . '/EndToEndTest.php';
    if (file_exists($e2eScript)) {
        $command = sprintf(
            'cd %s && php %s --chrome=%s 2>&1',
            escapeshellarg(dirname(__DIR__)),
            escapeshellarg($e2eScript),
            escapeshellarg($chromePath)
        );

        passthru($command, $e2eExitCode);

        if ($e2eExitCode !== 0) {
            $exitCode = $e2eExitCode;
            echo "\n❌ E2E tests failed!\n\n";
        } else {
            echo "\n✅ E2E tests passed!\n\n";
        }
    } else {
        echo "⚠️  E2E test script not found\n\n";
    }
}

echo "===========================================\n";
if ($exitCode === 0) {
    echo "✅ All tests passed!\n";
} else {
    echo "❌ Some tests failed!\n";
}
echo "===========================================\n";

exit($exitCode);