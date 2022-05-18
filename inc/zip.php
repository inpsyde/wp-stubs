<?php

declare(strict_types=1);

namespace Inpsyde\WpStubs;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @return array
 */
function findZipMethods(): array
{
    static $zipMethods;
    if (is_array($zipMethods)) {
        return $zipMethods;
    }

    $zipMethods = [];
    (new ExecutableFinder())->find('unzip') and $zipMethods[] = 'system';
    class_exists('ZipArchive') and $zipMethods[] = 'ZipArchive';
    if (!$zipMethods) {
        throw new \Error("Could not find a viable unzip method on this machine.");
    }

    $methodStr = (count($zipMethods) === 1) ? 'method' : 'methods';
    fwrite(STDOUT, sprintf("\nFound unzip %s: '%s'.", $methodStr, implode("', '", $zipMethods)));

    return $zipMethods;
}

/**
 * @param string $zipFilePath
 * @param string $target
 * @return bool
 */
function unzip(string $zipFilePath, string $target): bool
{
    $pathExists = file_exists($target);
    $pathReady = $pathExists ? delete($target) : mkdir($target, 0777, true);
    if (!$pathReady) {
        $message = $pathExists
            ? "Could not cleanup directory {$target}."
            : "Could not create directory {$target}.";
        fwrite(STDERR, "\n{$message}.");

        return false;
    }

    $unzipped = false;
    $zipMethods = findZipMethods();

    if (in_array('system', $zipMethods, true)) {
        $args = ['unzip', '-qq', '-o', $zipFilePath, '-d', $target];
        $process = new Process($args, WP_STUBS_DIR, null, null, 900);
        $unzipped = $process->run() === 0;
        if (!$unzipped) {
            fwrite(STDERR, "\n" . ($process->getErrorOutput() ?: $process->getOutput()));
            fwrite(STDERR, "\nFailed unzipping {$zipFilePath} to {$target} using unzip command.");
        }
    }

    if (!$unzipped && in_array('ZipArchive', $zipMethods, true)) {
        if (in_array('system', $zipMethods, true) && is_dir($target)) {
            if (!delete($target)) {
                fwrite(STDERR, "\nFailed deleting {$zipFilePath}.");

                return false;
            }
        }
        $zipArchive = new \ZipArchive();
        if ($zipArchive->open($zipFilePath) !== true) {
            fwrite(STDERR, "\nFailed opening {$zipFilePath} as archive using ZipArchive.");
        } elseif ($zipArchive->extractTo($target) === true) {
            $unzipped = true;
        }
        if (!$unzipped) {
            fwrite(STDERR, "\nFailed unzipping {$zipFilePath} to {$target} using ZipArchive.");
        }
        $zipArchive->close();
    }

    return $unzipped;
}
