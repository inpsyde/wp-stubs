<?php

declare(strict_types=1);

namespace Inpsyde\WpStubs;

use StubsGenerator\StubsGenerator;
use Symfony\Component\Finder\Finder;

const WP_STUBS_DIR = __DIR__;

try {
    if (!is_file(WP_STUBS_DIR . '/vendor/autoload.php')) {
        throw new \Error("Please install via Composer first.");
    }
    require_once 'vendor/autoload.php';
    if (!class_exists(\Symfony\Component\Process\ExecutableFinder::class)) {
        throw new \Error("Please update Composer dependencies first.");
    }

    findZipMethods();

    $toDownload = retrieveDownloadUrls(20);
    $all = count($toDownload);
    if ($all === 0) {
        $repoBuilt = null;
        if (!file_exists(__DIR__ . '/packages.json')) {
            fwrite(STDOUT, "\nStubs generation completed. Updating now Composer repo");
            $repoBuilt = buildComposerRepo(__DIR__);
        }
        exit(finalMessage(0, 0, $repoBuilt));
    }

    $success = 0;
    $stubsDir = str_replace('\\', '/', WP_STUBS_DIR . '/stubs');
    $generator = new StubsGenerator(
        StubsGenerator::FUNCTIONS
        | StubsGenerator::CLASSES
        | StubsGenerator::TRAITS
        | StubsGenerator::INTERFACES
        | StubsGenerator::CONSTANTS
    );
    $fixtures = buildFixtures(require WP_STUBS_DIR . '/fixtures.php');
    $visitor = NodeVisitor::new($fixtures);
    $latestDone = false;
    $shortDone = [];

    foreach ($toDownload as [$fullVer, $shortVer, $url]) {
        fwrite(STDOUT, "\n");
        $errMessage = "Stubs for WordPress '{$fullVer}' not created.";
        try {
            $wpPath = downloadWp($fullVer, $url);
            if (!$wpPath) {
                fwrite(STDOUT, "\n{$errMessage}");
                continue;
            }

            fwrite(STDOUT, "\nGenerating stubs for WP '{$fullVer}'...");
            $output = $generator->generate(buildFinder($wpPath), $visitor)->prettyPrint();

            writeOutput($output, "{$stubsDir}/{$fullVer}.php", $fullVer)
                ? $success++
                : fwrite(STDERR, "\nStubs for WP '{$fullVer}' not created.");

            if (!$latestDone) {
                $latestDone = true;
                $all++;
                writeOutput($output, "{$stubsDir}/latest.php", 'latest')
                    ? $success++
                    : fwrite(STDERR, "\nStubs for WP latest not written.");
            }

            if ($shortVer === $fullVer) {
                continue;
            }

            if (!empty($shortDone[$shortVer])) {
                $doneShort = $shortDone[$shortVer];
                fwrite(STDERR, "\nStubs for WP '{$shortVer}' already written from '{$doneShort}'.");
                continue;
            }

            if (!writeOutput($output, "{$stubsDir}/{$shortVer}.php", $shortVer)) {
                fwrite(STDERR, "\nStubs for WP '{$shortVer}' not written.");
                continue;
            }

            $shortDone[$shortVer] = $fullVer;
        } catch (\Throwable $throwable) {
            describeFailure($throwable, $errMessage);
            continue;
        }
    }

    fwrite(STDOUT, "\n");

    $repoBuilt = null;
    if (($all > 0) || !file_exists(__DIR__ . '/packages.json')) {
        fwrite(STDOUT, "\nStubs generation completed. Updating now Composer repo");
        $repoBuilt = buildComposerRepo(__DIR__);
    }

    exit(finalMessage($all, $success, $repoBuilt));
} catch (\Throwable $throwable) {
    function_exists(__NAMESPACE__ . '\\describeFailure')
        ? describeFailure($throwable)
        : fwrite(STDERR, sprintf("\n%s\n", $throwable->getMessage()));
    exit(1);
}
