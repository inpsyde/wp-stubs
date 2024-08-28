<?php

declare(strict_types=1);

namespace Inpsyde\WpStubs;

const WP_STUBS_DIR = __DIR__;

try {
    if (!is_file(WP_STUBS_DIR . '/vendor/autoload.php')) {
        throw new \Error("Please install via Composer first.");
    }
    require_once 'vendor/autoload.php';

    $commitHash = filter_var((string)($argv[1] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
    if (!isValid($commitHash)) {
        throw new \Error('Invalid commit hash');
    }

    $repoBuilt = buildComposerRepo(__DIR__, $commitHash);

    if ($repoBuilt) {
        fwrite(STDOUT, "\nReferences updated.\n");
        exit(0);
    }

    fwrite(STDERR, "\nReferences updating failed.\n");
    exit(1);
} catch (\Throwable $throwable) {
    function_exists(__NAMESPACE__ . '\\describeFailure')
        ? describeFailure($throwable)
        : fwrite(STDERR, sprintf("\n%s\n", $throwable->getMessage()));
    exit(1);
}

function isValid(string $commitHash): bool
{
    return preg_match('/^[0-9a-f]{40}$/', $commitHash) === 1;
}
