<?php

namespace setasign\Fpdi {
    if (!trait_exists(FpdiTrait::class, false)) {
        trait FpdiTrait
        {
            protected $readers = [];
            protected $createdObjs = [];
            protected $importedPages = [];
            protected $currentReaderId = null;
            protected function writePdfType($value) {}
            public function useImportedPage($pageId, $x = 0, $y = 0, $width = null, $height = null, $adjustPageSize = false) {}
            public function importPage($pageNumber, $boxName = 'CropBox', $groupXObject = true) {}
            public function setSourceFile($file) {}
        }
    }
}

namespace Psr\Log {
    if (!interface_exists(LoggerInterface::class)) {
        interface LoggerInterface
        {
            public function emergency($message, array $context = []);
            public function alert($message, array $context = []);
            public function critical($message, array $context = []);
            public function error($message, array $context = []);
            public function warning($message, array $context = []);
            public function notice($message, array $context = []);
            public function info($message, array $context = []);
            public function debug($message, array $context = []);
            public function log($level, $message, array $context = []);
        }
    }
    if (!interface_exists(LoggerAwareInterface::class)) {
        interface LoggerAwareInterface
        {
            public function setLogger(LoggerInterface $logger);
        }
    }
    if (!class_exists(NullLogger::class)) {
        class NullLogger implements LoggerInterface
        {
            public function emergency($message, array $context = []) {}
            public function alert($message, array $context = []) {}
            public function critical($message, array $context = []) {}
            public function error($message, array $context = []) {}
            public function warning($message, array $context = []) {}
            public function notice($message, array $context = []) {}
            public function info($message, array $context = []) {}
            public function debug($message, array $context = []) {}
            public function log($level, $message, array $context = []) {}
        }
    }
}

namespace Mpdf\PsrLogAwareTrait {
    if (!trait_exists(PsrLogAwareTrait::class, false)) {
        trait PsrLogAwareTrait
        {
            protected $logger;
            public function setLogger(\Psr\Log\LoggerInterface $logger)
            {
                $this->logger = $logger;
            }
        }
    }
    if (!trait_exists(MpdfPsrLogAwareTrait::class, false)) {
        trait MpdfPsrLogAwareTrait
        {
            protected $logger;
            public function setLogger(\Psr\Log\LoggerInterface $logger)
            {
                $this->logger = $logger;
            }
        }
    }
}

namespace MpdfService {
    if (!class_exists(MpdfService::class, false)) {
        class MpdfService
        {
            private static bool $registered = false;

            public static function registerAutoloader(): void
            {
                if (self::$registered) {
                    return;
                }
                self::$registered = true;
                spl_autoload_register(function ($class) {
                    $prefix = 'Mpdf\\';
                    $base_dir = __DIR__ . '/mpdf/src/';
                    $len = strlen($prefix);
                    if (strncmp($prefix, $class, $len) !== 0) {
                        return;
                    }
                    $relative_class = substr($class, $len);
                    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
                    if (file_exists($file)) {
                        require_once $file;
                    }
                });
            }

            public static function getInstance(array $config = []): \Mpdf\Mpdf
            {
                self::registerAutoloader();
                $tmpDir = defined('DATA') ? DATA . 'tmp' : sys_get_temp_dir();
                if (!is_dir($tmpDir)) {
                    @mkdir($tmpDir, 0755, true);
                }
                $defaultConfig = [
                    'tempDir' => $tmpDir,
                ];
                return new \Mpdf\Mpdf(array_merge($defaultConfig, $config));
            }
        }
    }
}

namespace PhpWordService {
    if (!class_exists(PhpWordService::class, false)) {
        class PhpWordService
        {
            private static bool $registered = false;

            public static function registerAutoloader(): void
            {
                if (self::$registered) {
                    return;
                }
                self::$registered = true;
                spl_autoload_register(function ($class) {
                    $prefix = 'PhpOffice\\PhpWord\\';
                    $base_dir = __DIR__ . '/PHPWord/src/PhpWord/';
                    $len = strlen($prefix);
                    if (strncmp($prefix, $class, $len) !== 0) {
                        return;
                    }
                    $relative_class = substr($class, $len);
                    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
                    if (file_exists($file)) {
                        require_once $file;
                    }
                });
            }

            public static function getInstance(): \PhpOffice\PhpWord\PhpWord
            {
                self::registerAutoloader();
                return new \PhpOffice\PhpWord\PhpWord();
            }
        }
    }
}

