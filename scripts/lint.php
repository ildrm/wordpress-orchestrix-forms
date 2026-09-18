<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$excluded = ['vendor', 'node_modules', '.git', 'build'];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$failed = 0;
$passed = 0;
foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || $file->getExtension() !== 'php') {
        continue;
    }
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
    if (array_filter($excluded, static fn (string $directory): bool => str_starts_with($relative, $directory . '/'))) {
        continue;
    }
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1';
    exec($command, $output, $code);
    if ($code !== 0) {
        fwrite(STDERR, implode(PHP_EOL, $output) . PHP_EOL);
        $failed++;
    } else {
        $passed++;
    }
    $output = [];
}
printf("PHP syntax: %d passed, %d failed\n", $passed, $failed);
exit($failed === 0 ? 0 : 1);
