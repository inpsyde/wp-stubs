<?php

declare(strict_types=1);

namespace Inpsyde\WpStubs;

use Symfony\Component\Process\Process;

/**
 * @param string $path
 * @return bool
 */
function rmdir(string $path): bool
{
    static $attempts;
    if (!isset($attempts)) {
        $attempts = 3;
    }

    try {
        if (!file_exists($path)) {
            return true;
        }
        if (!is_dir($path)) {
            return deleteFile($path);
        }
        $deleted = @rmdir($path);
        if ($deleted) {
            return true;
        }
        $isWindows = defined('PHP_WINDOWS_VERSION_BUILD');
        while ($isWindows && ($attempts > 0)) {
            $attempts--;
            usleep(350000);
            $deleted = @rmdir($path);
            if ($deleted) {
                break;
            }
        }
        $attempts = null;
        if ($deleted) {
            return true;
        }
        $error = error_get_last()['message'] ?? 'unknown reason';
        $message = "'Could not delete {$path}: {$error}";
        if ($isWindows) {
            $message .= "\nThis can be due to an antivirus or the Windows Search Indexer ";
            $message .= "locking the file while they are analyzed.";
        }
        fwrite(STDERR, $message);

        return $deleted;
    } catch (\Throwable $throwable) {
        describeFailure($throwable);

        return false;
    }
}

/**
 * @param string $path
 * @return bool
 */
function deleteRecursivePhp(string $path): bool
{
    if (!file_exists($path)) {
        return true;
    }
    if (!is_dir($path)) {
        return deleteFile($path);
    }
    try {
        try {
            $it = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        } catch (\UnexpectedValueException $e) {
            clearstatcache();
            usleep(100000);
            if (!is_dir($path)) {
                return true;
            }
            $it = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        }

        $ri = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        $dirs = [];
        foreach ($ri as $file) {
            if ($file->isDir()) {
                $dirs[] = $file->getPathname();
                continue;
            }
            deleteFile($file->getPathname());
        }
        foreach ($dirs as $dir) {
            rmdir($dir);
        }

        return rmdir($path);
    } catch (\Throwable $throwable) {
        describeFailure($throwable, "Failed deleting {$path}");

        return false;
    }
}

/**
 * @param string $path
 * @return bool
 */
function deleteFile(string $path): bool
{
    try {
        if (!file_exists($path)) {
            return true;
        }
        if (!is_file($path)) {
            return delete($path);
        }
        $attempt = 3;
        while ($attempt > 0) {
            @unlink($path);
            usleep(100000);
            if (!is_file($path)) {
                return true;
            }
            $attempt--;
        }

        fwrite(STDERR, "\nFailed deleting '{$path}'.");

        return false;
    } catch (\Throwable $throwable) {
        describeFailure($throwable, "Failed deleting '{$path}'.");

        return false;
    }
}

/**
 * @param string $path
 * @return bool
 */
function delete(string $path): bool
{
    if (!file_exists($path)) {
        return true;
    }

    if (!is_dir($path)) {
        return deleteFile($path);
    }

    $isWindows = defined('PHP_WINDOWS_VERSION_BUILD');

    try {
        if ($isWindows) {
            clearstatcache(true, $path);
            $stat = (is_dir($path) || is_link($path)) ? lstat($path) : [];
            if ($stat && (($stat['mode'] & 0xF000) !== 0x4000)) {
                $path = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
                $deleted = rmdir($path);
                $deleted or fwrite(STDERR, "\nFailed deleting junction '{$path}'.");

                return $deleted;
            }
        }

        if (is_link($path)) {
            $deleted = unlink($path);
            $deleted or fwrite(STDERR, "\nFailed deleting link '{$path}'.");

            return $deleted;
        }

        if (\function_exists('proc_open')) {
            $command = $isWindows
                ? sprintf('rmdir /s /q %s', realpath($path))
                : "rm -rf {$path}";
            $process = Process::fromShellCommandline($command, WP_STUBS_DIR, null, null, 1800);
            $result = $process->run() === 0;
            clearstatcache();
            if ($result && !file_exists($path)) {
                return true;
            }
            fwrite(STDERR, "\n" . $process->getErrorOutput());
        }

        if (!deleteRecursivePhp($path)) {
            fwrite(STDERR, "\nFailed deleting '{$path}'.");

            return false;
        }

        return true;
    } catch (\Throwable $throwable) {
        describeFailure($throwable, "Failed deleting '{$path}'.");

        return false;
    }
}

/**
 * @param int $all
 * @param int $success
 * @param bool|null $repoBuilt
 * @return int
 */
function finalMessage(int $all, int $success, ?bool $repoBuilt): int
{
    if ($all === 0) {
        fwrite(STDOUT, "\nNothing to process.\n");

        return 0;
    }

    $versionStr = $all === 1 ? 'one WP version' : sprintf('%d WP versions', $all);

    if ($success === $all) {
        $message = sprintf("Done creating stubs for %s.", $versionStr);
        fwrite(STDOUT, "\n{$message}");
        composerRepoFinalMessage($repoBuilt, true);

        return $repoBuilt !== false ? 0 : 1;
    }

    $error = (($success === 0) || ($all === 1))
        ? sprintf("Failed creating stubs for %s.", $versionStr)
        : sprintf("Failed creating stubs for %d out of %s.", $all - $success, $versionStr);
    fwrite(STDERR, "\n{$error}");
    composerRepoFinalMessage($repoBuilt, false);

    return 1;
}

/**
 * @param bool|null $result
 * @param bool $otherResult
 * @return void
 */
function composerRepoFinalMessage(?bool $result, bool $otherResult): void
{
    if ($result) {
        return;
    }

    [$message, $stream] = match($result) {
        false => ['Composer repository update failed', STDERR],
        null => ['Composer repository unchanged.', STDOUT],
    };

    if ($result === false) {
        $message = ($otherResult === true) ? "However, {$message}!" : "{$message} as well!";
    }

    fwrite($stream, "\n" . $message);
}

/**
 * @param \Throwable $throwable
 * @param string|null $message
 * @param int $depth
 * @return void
 */
function describeFailure(\Throwable $throwable, ?string $message = null, int $depth = 1): void
{
    $message and fwrite(STDERR, "\n{$message}");

    fwrite(
        STDERR,
        sprintf(
            "\n%s%s: %s (in '%s' line %d)",
            str_repeat("\t", $depth),
            get_class($throwable),
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine()
        )
    );
    $prev = $throwable->getPrevious();
    $prev and describeFailure($prev, null, $depth + 1);
}
