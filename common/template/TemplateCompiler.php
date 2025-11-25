<?php

namespace Template;

use RuntimeException;

/**
 * Compile templating syntax into native PHP file, avoiding eval at runtime.
 */
class TemplateCompiler
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, 0755, true)) {
                throw new RuntimeException('Unable to create template cache directory: ' . $this->cacheDir);
            }
        }
    }

    public function getCompiledPath(string $sourceFile): string
    {
        $hash = md5($sourceFile);
        $baseName = basename($sourceFile);
        return $this->cacheDir . $hash . '_' . $baseName . '.php';
    }

    public function isFresh(string $sourceFile, string $compiledFile): bool
    {
        if (!file_exists($compiledFile)) {
            return false;
        }
        return filemtime($compiledFile) >= filemtime($sourceFile);
    }

    public function compile(string $sourceFile, string $compiledFile, string $content): void
    {
        $compiled = "<?php /* Compiled from {$sourceFile} */ ?>" . $content;
        if (file_put_contents($compiledFile, $compiled) === false) {
            throw new RuntimeException('Unable to write compiled template: ' . $compiledFile);
        }
    }
}

