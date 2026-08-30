#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Executable docs guard: every ```php fenced block in README.md and docs/**
 * must compile. Blocks carrying the "// runnable" marker are additionally
 * executed in isolation with the package autoloader.
 *
 * Compile checks use token_get_all(TOKEN_PARSE) — syntax only, no symbol
 * resolution, so framework-illustrating snippets stay valid.
 *
 * Usage: php tools/ensure-docs-snippets.php [file-or-dir ...]
 */

$root = dirname(__DIR__);

$paths = array_slice($argv, 1);

if ($paths === []) {
    $paths = ['README.md', 'docs'];
}

$files = [];

foreach ($paths as $path) {
    $full = $root . '/' . $path;

    if (is_dir($full)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($full, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $files[] = $file->getPathname();
            }
        }

        continue;
    }

    if (is_file($full)) {
        $files[] = $full;
    }
}

$checked = 0;
$failures = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);

    if ($content === false) {
        fwrite(STDERR, "Could not read {$file}\n");
        $failures++;

        continue;
    }

    preg_match_all('/```php\s*\n(.*?)```/s', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $index => $match) {
        $checked++;
        $snippet = preg_replace('/^\s*<\?php\s*/', '', $match[1]) ?? $match[1];
        $runnable = str_contains($snippet, '// runnable');
        $label = $file . '#' . $index;

        try {
            token_get_all('<?php ' . $snippet, TOKEN_PARSE);
        } catch (ParseError $exception) {
            fwrite(STDERR, "Syntax failure in {$label}: {$exception->getMessage()}\n");
            $failures++;

            continue;
        }

        if (!$runnable) {
            continue;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'docsnippet');

        if ($tmp === false) {
            fwrite(STDERR, "Could not create temp file for {$label}\n");
            $failures++;

            continue;
        }

        $runner = "<?php\n\ndeclare(strict_types=1);\n\nrequire " . var_export($root . '/vendor/autoload.php', true) . ";\n\n" . $snippet;
        file_put_contents($tmp, $runner);

        $cmd = 'php ' . escapeshellarg($tmp) . ' 2>&1';
        exec($cmd, $out, $code);

        unlink($tmp);

        if ($code !== 0) {
            fwrite(STDERR, "Runtime failure in runnable snippet {$label}:\n" . implode("\n", $out) . "\n");
            $failures++;
        }
    }
}

printf("Docs snippets: %d checked, %d failed\n", $checked, $failures);

exit($failures > 0 ? 1 : 0);
