<?php

declare(strict_types=1);

namespace Inpsyde\WpStubs;

use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

const EXCLUDED_WP_DIRS = [
    'wp-includes/random_compat',
    'wp-includes/sodium_compat',
];

const EXCLUDED_WP_FILES = [
    'wp-admin/includes/noop.php',
    'wp-includes/cache-compat.php',
    'wp-includes/compat.php',
];

/**
 * @param string $wpPath
 * @return Finder
 */
function buildFinder(string $wpPath): Finder
{
    $excludedFiles = array_map(fn(string $file) => $wpPath . "/{$file}", EXCLUDED_WP_FILES);

    return Finder::create()
        ->in($wpPath)
        ->ignoreDotFiles(true)
        ->ignoreVCS(true)
        ->exclude(EXCLUDED_WP_DIRS)
        ->name('*.php')
        ->filter(
            static function (\SplFileInfo $info) use ($excludedFiles): bool {
                $path = str_replace('\\', '/', $info->getPathname());

                return !in_array($path, $excludedFiles, true);
            }
        );
}

/**
 * @param array $fixturesData
 * @return array
 */
function buildFixtures(array $fixturesData): array
{
    $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

    $fixtures = [];

    foreach ($fixturesData as $namespace => $fixtureTypesData) {
        isset($fixtures[$namespace]) or $fixtures[$namespace] = [];
        foreach ($fixtureTypesData as $type => $elements) {
            isset($fixtures[$namespace][$type]) or $fixtures[$namespace][$type] = [];
            foreach ($elements as $id => $element) {
                $parsed = $parser->parse("<?php\n\n{$element}");
                $fixtures[$namespace][$type][$id] = reset($parsed);
            }
        }
    }

    return $fixtures;
}

/**
 * @param string $output
 * @param string $targetPath
 * @param string $version
 * @return bool
 */
function writeOutput(string $output, string $targetPath, string $version): bool
{
    fwrite(STDOUT, "\nWriting output to {$targetPath}...");

    if (file_exists($targetPath) && !delete($targetPath)) {
        fwrite(
            STDERR,
            "\nFailed writing stubs for WordPress {$version} to '{$targetPath}'. "
            . "File exists already and could not be deleted."
        );

        return false;
    }

    if (!$output) {
        fwrite(STDERR, "\nFailed generating stubs for WordPress {$version}.");

        return false;
    }

    if (!file_put_contents($targetPath, $output)) {
        fwrite(STDERR, "\nFailed writing stubs for WordPress {$version} to {$targetPath}.");

        return false;
    }

    fwrite(STDOUT, "\nStubs for WordPress {$version} written to {$targetPath}.");

    return true;
}
