<?php

declare(strict_types=1);

namespace Inpsyde\WpStubs;

/**
 * @param int $versionLimit
 * @return array<array{string, string, string}>
 */
function retrieveDownloadUrls(int $versionLimit = 5): array
{
    $versions = file_get_contents('https://api.wordpress.org/core/version-check/1.7/');
    $versionsData = $versions ? json_decode($versions, true) : null;
    if (
        !$versionsData
        || !is_array($versionsData)
        || empty($versionsData['offers'])
        || !is_array($versionsData['offers'])
    ) {
        throw new \Error("Could not receive WordPress version data from WP.org API.");
    }

    $toDownload = [];
    $errors = 0;
    $stubsDir = str_replace('\\', '/', WP_STUBS_DIR . '/stubs');
    $urlRxp = '{^https://downloads\.wordpress\.org/release/wordpress-([0-9\.]+)-no-content\.zip$}';
    $versions = [];
    foreach ($versionsData['offers'] as $versionData) {
        $packages = $versionData['packages'] ?? null;
        $noContentUrl = is_array($packages) ? ($packages['no_content'] ?? null) : null;
        if (!$noContentUrl || !filter_var($noContentUrl, FILTER_VALIDATE_URL)) {
            $errors++;
            fwrite(STDERR, "\nWill not download: '{$noContentUrl}' because invalid URL.");
            continue;
        }
        preg_match($urlRxp, $noContentUrl, $matches);
        if (empty($matches[1])) {
            $errors++;
            fwrite(STDERR, "\nWill not download: '{$noContentUrl}' because invalid URL.");
            continue;
        }
        $ver = $matches[1];
        $versions[$ver] = (object)['ver' => $ver, 'url' => $noContentUrl];
    }

    $total = count($versions);
    if ($total > 0) {
        uasort($versions, fn(\stdClass $a, \stdClass $b) => version_compare($b->ver, $a->ver));
        ($total > $versionLimit) and $versions = array_splice($versions, 0, $versionLimit);
    }

    foreach ($versions as $version) {
        $fullVer = $version->ver;
        $shortVer = implode('.', array_pad(array_slice(explode('.', $fullVer), 0, 2), 2, '0'));
        if (file_exists("{$stubsDir}/{$fullVer}.php")) {
            fwrite(STDOUT, "\nStubs for WP version '{$fullVer}' already there.");
            continue;
        }

        fwrite(STDOUT, "\nWill generate stubs for WP version: '{$fullVer}'.");
        $toDownload[] = [$fullVer, $shortVer, $version->url];
    }

    if (!$toDownload && ($errors > 0)) {
        throw new \Error("\nFailed finding WordPress version to process.");
    }

    return $toDownload;
}

/**
 * @param string $version
 * @param string $url
 * @return string|null
 */
function downloadWp(string $version, string $url): ?string
{
    $zipFilePath = str_replace('\\', '/', WP_STUBS_DIR . "/archives/{$version}.zip");
    $versionDir = str_replace('.', '_', $version);
    $targetPath = str_replace('\\', '/', WP_STUBS_DIR . "/files/{$versionDir}");
    if (is_dir("{$targetPath}/wordpress")) {
        fwrite(STDOUT, "\nWP {$version} files already downloaded.");

        return "{$targetPath}/wordpress";
    }

    if (file_exists($zipFilePath)) {
        fwrite(STDOUT, "\nWP {$version} zip file already downloaded.");
    } else {
        fwrite(STDOUT, "\nDownloading WP {$version}...");
        if (!file_put_contents($zipFilePath, fopen($url, 'r'))) {
            fwrite(STDERR, "\nFailed saving {$url} to {$zipFilePath}.");

            return null;
        }
    }

    fwrite(STDOUT, "\nWP {$version} zip downloaded, unzipping...");
    $unzipped = unzip($zipFilePath, $targetPath);
    if (!$unzipped) {
        return null;
    }

    deleteFile($zipFilePath);

    return "{$targetPath}/wordpress";
}
