<?php

namespace Core\Media;

use Core\Media\FileManager;
use Core\Lang;
use Core\Auth\UsersManager;
use Core\Router\Router;
use Utils\Util;
use Core\Core;

/**
 * Service façade for everything related to media/files.
 */
defined('ROOT') or exit('Access denied!');

class MediaService
{
    private string $rootDir;
    private string $legacyDataFile;
    private string $dataFile;
    private bool $routesRegistered = false;
    private array $adminCss = ['templates/filemanager/admin.css'];
    private array $adminJs = ['templates/filemanager/admin.js'];
    private array $adminModules = [
        [
            'name' => 'filemanager',
            'label' => 'filemanager.name',
            'icon' => 'fa-regular fa-file',
        ],
    ];

    public function __construct()
    {
        $this->rootDir = rtrim(UPLOAD . 'files', '/') . '/';
        $this->legacyDataFile = DATA_PLUGIN . 'filemanager/files.json';
        $this->dataFile = DATA_CORE_MEDIA . 'files.json';

        $this->bootstrapStorage();
    }

    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    public function makeManager(?string $relative = ''): FileManager
    {
        [, $absolute] = $this->normalizePath($relative);
        return new FileManager($absolute);
    }

    public function renderManagerButton($textareaId = false, $buttonLabel = null): string
    {
        $buttonLabel = $buttonLabel ?: Lang::get('filemanager.button-label');
        $token = UsersManager::getCurrentUser()?->token ?? '';
        if ($token === '') {
            return '';
        }
        $url = Router::getInstance()->generate('filemanager-view-ajax-home', [
            'token' => $token,
            'editor' => $textareaId ?: '',
        ]);

        return sprintf(
            '<a class="button fmUploadButton" data-fancybox data-type="ajax" href="%s"/><i class="fa-solid fa-file-image"></i> %s</a>',
            $url,
            $buttonLabel
        );
    }

    /**
     * Normalize a relative path and return [cleanRelative, absolute].
     */
    public function normalizePath(?string $relative): array
    {
        $relative = $relative ?? '';
        if ($relative === 'Back%To%Home%') {
            $relative = '';
        }
        $relative = trim($relative, '/');

        if ($relative === '') {
            return ['', $this->rootDir];
        }

        $parts = [];
        foreach (explode('/', $relative) as $part) {
            $part = trim($part);
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        $clean = implode('/', $parts);
        $absolute = $this->rootDir . ($clean !== '' ? $clean . '/' : '');

        if (!is_dir($absolute)) {
            @mkdir($absolute, 0755, true);
        }

        return [$clean, $absolute];
    }

    private function bootstrapStorage(): void
    {
        if (!is_dir($this->rootDir)) {
            @mkdir($this->rootDir, 0755, true);
        }

        if (!is_dir(DATA_CORE_MEDIA)) {
            @mkdir(DATA_CORE_MEDIA, 0755, true);
        }

        if (!file_exists($this->dataFile)) {
            if (file_exists($this->legacyDataFile)) {
                $payload = Util::readJsonFile($this->legacyDataFile, true);
                Util::writeJsonFile($this->dataFile, $payload);
            } else {
                Util::writeJsonFile($this->dataFile, []);
            }
        }
    }

    public function registerRoutes(): void
    {
        if ($this->routesRegistered) {
            return;
        }
        $this->routesRegistered = true;

        $router = Router::getInstance();
        $router->map('GET', '/admin/filemanager[/?]', 'Core\Media\Controllers\FileManagerAPIController#home', 'filemanager-home');
        $router->map('POST', '/admin/filemanager/view-ajax/upload/[a:token]', 'Core\Media\Controllers\FileManagerAPIController#upload', 'filemanager-upload');
        $router->map('POST', '/admin/filemanager/view-ajax/delete/[a:token]', 'Core\Media\Controllers\FileManagerAPIController#delete', 'filemanager-delete');
        $router->map('POST', '/admin/filemanager/view-ajax/create/[a:token]', 'Core\Media\Controllers\FileManagerAPIController#create', 'filemanager-create');
        $router->map('POST', '/admin/filemanager/view', 'Core\Media\Controllers\FileManagerAPIController#view', 'filemanager-view');
        $router->map('POST', '/admin/filemanager/view-ajax', 'Core\Media\Controllers\FileManagerAPIController#viewAjax', 'filemanager-view-ajax');
        $router->map('GET', '/admin/filemanager/view-ajax/[a:token]/[*:editor]?', 'Core\Media\Controllers\FileManagerAPIController#viewAjaxHome', 'filemanager-view-ajax-home');
        $router->map('POST', '/admin/filemanager/api/upload/[a:token]', 'Core\Media\Controllers\FileManagerAPIController#uploadAPI', 'filemanager-upload-api');
    }

    public function getAdminCssUrls(): array
    {
        if (!$this->isCurrentModule()) {
            return [];
        }
        return array_map(fn($path) => Util::urlBuild('admin/' . ltrim($path, '/')), $this->adminCss);
    }

    public function getAdminJsUrls(): array
    {
        if (!$this->isCurrentModule()) {
            return [];
        }
        return array_map(fn($path) => Util::urlBuild('admin/' . ltrim($path, '/')), $this->adminJs);
    }

    public function getAdminNavigationEntries(): array
    {
        $entries = [];
        foreach ($this->adminModules as $module) {
            $entries[] = [
                'name' => $module['name'],
                'icon' => $module['icon'],
                'label' => Lang::get($module['label']),
            ];
        }
        return $entries;
    }

    public function isCoreAdminModule(string $name): bool
    {
        foreach ($this->adminModules as $module) {
            if ($module['name'] === $name) {
                return true;
            }
        }
        return false;
    }

    protected function isCurrentModule(): bool
    {
        return Core::getInstance()->getPluginToCall() === 'filemanager';
    }
}


