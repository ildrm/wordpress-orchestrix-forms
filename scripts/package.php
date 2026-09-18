<?php

declare(strict_types=1);

if (! class_exists(ZipArchive::class)) {
    fwrite(STDERR, "ZipArchive is required.\n");
    exit(1);
}
$root = dirname(__DIR__);
$build = $root . DIRECTORY_SEPARATOR . 'build';
if (! is_dir($build) && ! mkdir($build, 0775, true) && ! is_dir($build)) {
    throw new RuntimeException('Unable to create build directory.');
}
$path = $build . DIRECTORY_SEPARATOR . 'orchestrix-forms-1.0.0.zip';
$zip = new ZipArchive();
if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Unable to create package.');
}
$excluded = ['.git/', 'build/', 'node_modules/', 'vendor/', 'tests/', 'scripts/', '.phpunit.cache/'];
$allowedRoot = ['orchestrix-forms.php', 'uninstall.php', 'README.md', 'LICENSE', 'CHANGELOG.md', 'SECURITY.md'];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || ! $file->isFile()) {
        continue;
    }
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    if (array_filter($excluded, static fn (string $prefix): bool => str_starts_with($relative, $prefix))) {
        continue;
    }
    $top = explode('/', $relative)[0];
    if (! in_array($top, ['src', 'assets', 'languages', 'docs'], true) && ! in_array($relative, $allowedRoot, true)) {
        continue;
    }
    if (str_starts_with($relative, 'assets/src/')) {
        continue;
    }
    $zip->addFile($file->getPathname(), 'orchestrix-forms/' . $relative);
}
$zip->close();
printf("Package: %s\nSHA-256: %s\n", $path, hash_file('sha256', $path));
