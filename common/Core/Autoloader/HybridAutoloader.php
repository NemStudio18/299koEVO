<?php

namespace Core\Autoloader;

/**
 * Minimal portable HybridAutoloader with automatic cache generation and validation
 * - Resolves classes to files from a base directory (PSR-0/4 style mapping)
 * - Optional cache file path (not required to function)
 * - Automatic cache generation and validation based on file timestamps
 * - Exposes basic stats used by views/controllers
 */
class HybridAutoloader
{
    private array $baseDirs = [];
    private string $cacheFile;
    private bool $debug;
    private bool $authoritative;
    private array $loadedClasses = [];
    private ?array $classMap = null;
    private ?int $cacheTimestamp = null;

    /**
     * @param string|array $baseDirs Base directory or array of base directories
     */
    public function __construct($baseDirs, string $cacheFile, bool $debug = false, bool $authoritative = false)
    {
        $dirs = is_array($baseDirs) ? $baseDirs : [$baseDirs];
        foreach ($dirs as $dir) {
            $this->baseDirs[] = rtrim(str_replace('\\', '/', $dir), '/');
        }
        $this->cacheFile = $cacheFile;
        $this->debug = $debug;
        $this->authoritative = $authoritative;
        spl_autoload_register([$this, 'autoload'], true, true);
        $this->loadCache();
    }

    public function loadCache(): void
    {
        if (!$this->authoritative) {
            return;
        }

        if (is_file($this->cacheFile)) {
            $cacheData = @include $this->cacheFile;
            if (is_array($cacheData) && isset($cacheData['__metadata__'])) {
                $this->cacheTimestamp = $cacheData['__metadata__']['generated_at'] ?? null;
                $this->classMap = $cacheData['__classmap__'] ?? null;
                
                // Validate cache: check if any file has been modified
                if ($this->isCacheValid()) {
                    return;
                }
            }
        }
        
        // Cache invalid or doesn't exist, generate it
        $this->generateCache();
    }

    /**
     * Check if cache is still valid by comparing file timestamps
     */
    private function isCacheValid(): bool
    {
        if ($this->classMap === null || $this->cacheTimestamp === null) {
            return false;
        }

        // Check if cache file itself exists and is readable
        if (!is_file($this->cacheFile)) {
            return false;
        }

        // Check if any mapped file has been modified since cache generation
        foreach ($this->classMap as $class => $data) {
            $file = is_array($data) ? $data['file'] : $data;
            $baseIndex = is_array($data) ? ($data['base'] ?? 0) : 0;
            
            if (!isset($this->baseDirs[$baseIndex])) {
                return false;
            }

            $fullPath = $this->baseDirs[$baseIndex] . '/' . $file;
            if (!is_file($fullPath)) {
                return false; // File was deleted
            }
            if (filemtime($fullPath) > $this->cacheTimestamp) {
                return false; // File was modified
            }
        }

        return true;
    }

    /**
     * Generate cache by scanning all PHP files in base directories
     */
    public function generateCache(): void
    {
        if (!$this->authoritative) {
            return;
        }

        $classMap = [];
        
        foreach ($this->baseDirs as $index => $baseDir) {
            if (!is_dir($baseDir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $fullPath = $file->getPathname();
                    $relativePath = str_replace('\\', '/', substr($fullPath, strlen($baseDir) + 1));
                    
                    // Extract namespace and class name from file
                    $classInfo = $this->extractClassFromFile($fullPath);
                    if ($classInfo !== null) {
                        list($namespace, $className) = $classInfo;
                        $fullClassName = $namespace ? $namespace . '\\' . $className : $className;
                        
                        // Store path and the index of the base directory
                        $classMap[$fullClassName] = [
                            'file' => $relativePath,
                            'base' => $index
                        ];
                    }
                }
            }
        }

        // Create cache directory if it doesn't exist
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        // Write cache file with metadata
        $cacheData = [
            '__metadata__' => [
                'generated_at' => time(),
                'base_directories' => $this->baseDirs,
                'total_classes' => count($classMap)
            ],
            '__classmap__' => $classMap
        ];

        $cacheContent = "<?php\n";
        $cacheContent .= "// Autoloader cache generated at " . date('Y-m-d H:i:s') . "\n";
        $cacheContent .= "// Do not edit this file manually\n\n";
        $cacheContent .= "return " . var_export($cacheData, true) . ";\n";

        @file_put_contents($this->cacheFile, $cacheContent, LOCK_EX);
        
        $this->classMap = $classMap;
        $this->cacheTimestamp = time();

        if ($this->debug) {
            error_log("Autoloader cache generated: " . count($classMap) . " classes mapped from " . count($this->baseDirs) . " directories");
        }
    }

    /**
     * PSR-4 implementation that works across multiple base directories
     */
    public function load(string $class): bool
    {
        if (isset($this->loadedClasses[$class])) {
            return true;
        }

        // 1. Check in ClassMap
        if (is_array($this->classMap) && isset($this->classMap[$class])) {
            $data = $this->classMap[$class];
            $file = is_array($data) ? $data['file'] : $data;
            $baseIndex = is_array($data) ? ($data['base'] ?? 0) : 0;
            
            if (isset($this->baseDirs[$baseIndex])) {
                $fullPath = $this->baseDirs[$baseIndex] . '/' . $file;
                if (is_file($fullPath)) {
                    require_once $fullPath;
                    $this->loadedClasses[$class] = $fullPath;
                    return true;
                }
            }
        }

        // 2. Fallback to PSR-4 lookup in each base directory (with case tolerance for plugin dirs)
        $psrPath = str_replace('\\', '/', $class) . '.php';
        $pathCandidates = [$psrPath];
        
        $segments = explode('/', $psrPath);
        if (count($segments) > 1) {
            $lowerFirst = $segments;
            $lowerFirst[0] = strtolower($lowerFirst[0]);
            $pathCandidates[] = implode('/', $lowerFirst);
            
            $lcFirst = $segments;
            $lcFirst[0] = lcfirst($lcFirst[0]);
            $pathCandidates[] = implode('/', $lcFirst);
        }

        foreach ($this->baseDirs as $baseDir) {
            foreach ($pathCandidates as $candidate) {
                $fullPath = $baseDir . '/' . $candidate;
                if (is_file($fullPath)) {
                    require_once $fullPath;
                    $this->loadedClasses[$class] = $fullPath;
                    
                    // Add to class map if authoritative
                    if ($this->authoritative && is_array($this->classMap)) {
                        $index = array_search($baseDir, $this->baseDirs);
                        $this->classMap[$class] = [
                            'file' => $candidate,
                            'base' => $index
                        ];
                    }
                    return true;
                }
            }
        }

        return false;
    }

    public function autoload(string $class): void
    {
        $this->load($class);
    }

    /**
     * Extract namespace and class name from PHP file
     */
    private function extractClassFromFile(string $filePath): ?array
    {
        $content = @file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $namespace = null;
        $className = null;

        // Extract namespace
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = trim($matches[1]);
        }

        // Extract class name (class, interface, trait)
        if (preg_match('/\b(?:class|interface|trait)\s+([a-zA-Z_][a-zA-Z0-9_]*)\b/', $content, $matches)) {
            $className = $matches[1];
        }

        if ($className === null) {
            return null;
        }

        return [$namespace, $className];
    }

    public function useClass(string $class): bool
    {
        return $this->load($class);
    }

    /**
     * Force cache regeneration
     */
    public function regenerateCache(): void
    {
        $this->generateCache();
    }

    /**
     * Clear cache file
     */
    public function clearCache(): void
    {
        if (is_file($this->cacheFile)) {
            @unlink($this->cacheFile);
        }
        $this->classMap = null;
        $this->cacheTimestamp = null;
    }

    public function getLoadedClasses(): array
    {
        return $this->loadedClasses;
    }

    public function getStats(): array
    {
        return [
            'total_classes_mapped' => is_array($this->classMap) ? count($this->classMap) : 0,
            'loaded_classes' => count($this->loadedClasses),
            'cache_file' => str_replace('\\', '/', $this->cacheFile),
            'base_directories' => $this->baseDirs,
            'cache_valid' => $this->isCacheValid(),
            'cache_timestamp' => $this->cacheTimestamp,
            'authoritative' => $this->authoritative,
        ];
    }
}

