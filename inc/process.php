<?php

declare(strict_types=1);

namespace Inpsyde\WpStubs;

use PhpParser\ParserFactory;
use StubsGenerator\Result;
use StubsGenerator\StubsGenerator;
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

const VERSIONED_PACKAGE_NAME = 'inpsyde/wp-stubs-versions';

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
 * @param string $wpPath
 * @return string
 */
function generateForWpPath(string $wpPath): string
{
    $generator = new StubsGenerator(
        StubsGenerator::FUNCTIONS
        | StubsGenerator::CLASSES
        | StubsGenerator::TRAITS
        | StubsGenerator::INTERFACES
        | StubsGenerator::CONSTANTS
    );
    $fixtures = buildFixtures(require WP_STUBS_DIR . '/fixtures.php');
    $visitor = NodeVisitor::new($fixtures);

    return $generator->generate(buildFinder($wpPath), $visitor)->prettyPrint();
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

/**
 * @param string $targetDir
 * @return bool
 */
function buildComposerRepo(string $targetDir): bool
{
    $data = ['packages' => [VERSIONED_PACKAGE_NAME => []]];
    $finder = Finder::create()->in("{$targetDir}/stubs")
        ->files()
        ->name('*.php')
        ->sortByModifiedTime()
        ->reverseSorting();
    $basePackage = [
        'name' => VERSIONED_PACKAGE_NAME,
        'version' => '',
        'dist' => [
            'url' => 'https://raw.githubusercontent.com/inpsyde/wp-stubs/main/stubs/%s.php',
            'type' => 'file',
        ],
    ];
    foreach ($finder as $file) {
        $basename = $file->getBasename('.php');
        $package = $basePackage;
        $package['version'] = ($basename === 'latest') ? 'dev-latest' : $basename;
        $package['dist']['url'] = sprintf($package['dist']['url'], $basename);
        $data['packages'][VERSIONED_PACKAGE_NAME][] = $package;
    }

    return writeComposerRepo($data, "{$targetDir}/packages.json");
}

/**
 * @param array $data
 * @param string $targetPath
 * @return bool
 */
function writeComposerRepo(array $data, string $targetPath): bool
{
    fwrite(STDOUT, "\nWriting Composer repository to {$targetPath}...");

    if (file_exists($targetPath) && !delete($targetPath)) {
        fwrite(
            STDERR,
            "\nFailed writing Composer repository to '{$targetPath}'. "
            . "File exists already and could not be deleted."
        );

        return false;
    }

    if (empty($data['packages'][VERSIONED_PACKAGE_NAME])) {
        fwrite(STDERR, "\nFailed generating Composer repository: no package data.");

        return false;
    }

    try {
        $output = json_encode($data, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $throwable) {
        fwrite(STDERR, "\nFailed encoding Composer repository JSON.");
        fwrite(STDERR, "\n" . $throwable->getMessage());

        return false;
    }

    if (!file_put_contents($targetPath, $output)) {
        fwrite(STDERR, "\nFailed writing Composer repository to {$targetPath}.");

        return false;
    }

    fwrite(STDOUT, "\nComposer repository written to {$targetPath}.");

    return true;
}
