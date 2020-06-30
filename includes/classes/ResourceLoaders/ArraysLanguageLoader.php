<?php
/**
 *
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id:  $
 */

namespace Zencart\LanguageLoader;

use Zencart\FileSystem\FileSystem;

class ArraysLanguageLoader extends BaseLanguageLoader
{
    public function makeConstants($defines)
    {
        foreach ($defines as $defineKey => $defineValue) {
            if (defined($defineKey)) {
                continue;
            }
            preg_match_all('/%{2}([^%]+)%{2}/', $defineValue, $matches, PREG_PATTERN_ORDER);
            if (count($matches[1])) {
                foreach ($matches[1] as $index => $match) {
                    if (isset($defines[$match])) {
                        $defineValue = str_replace($matches[0][$index], $defines[$match], $defineValue);
                    }
                }
            }
            define($defineKey, $defineValue);
        }
    }

    public function getLanguageDefines()
    {
        return $this->languageDefines;
    }


    protected function loadArraysFromDirectory($rootPath, $language, $extraPath)
    {
        $path = $rootPath . $language . $extraPath;
        $fileList = $this->fileSystem->listFilesFromDirectory($path, '~^(lang\.).*\.php$~i');
        $defineList = $this->processArrayFileList($path, $fileList);
        return $defineList;
    }

    protected function pluginLoadArraysFromDirectory($language, $extraPath)
    {
        $defineList = [];
        foreach ($this->pluginList as $plugin) {
            $pluginDir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'] . '/admin/includes/languages/';
            $defines = $this->loadArraysFromDirectory($pluginDir, $language, $extraPath);
            $defineList = array_merge($defineList, $defines);
        }
        return $defineList;
    }

    protected function processArrayFileList($path, $fileList)
    {
        $defineList = [];
        foreach ($fileList as $file) {
            $defines = $this->loadArrayDefineFile($path . '/' . $file);
            $defineList = array_merge($defineList, $defines);
        }
        return $defineList;
    }

    public function loadDefinesFromArrayFile($rootPath, $language, $fileName, $extraPath = '')
    {
        $arrayFileName = 'lang.' . $fileName;
        $mainFile = $rootPath . $language . $extraPath. '/' . $arrayFileName;
        $fallbackFile = $rootPath . $this->fallback . $extraPath . '/' . $arrayFileName;
        $defineList = $this->loadDefinesWithFallback($mainFile, $fallbackFile);
        return $defineList;
    }

    public function pluginLoadDefinesFromArrayFile($language, $fileName, $context = 'admin', $extraPath = '')
    {
        $defineList = [];
        foreach ($this->pluginList as $plugin) {
            $pluginDir = DIR_FS_CATALOG . 'zc_plugins/' . $plugin['unique_key'] . '/' . $plugin['version'];
            $pluginDefineList = $this->loadDefinesFromArrayFile($pluginDir . '/' . $context . '/includes/languages/', $language, $fileName, $extraPath);
            $defineList = array_merge($defineList, $pluginDefineList);
        }
        return $defineList;
    }

    protected function loadDefinesWithFallback($mainFile, $fallbackFile)
    {
        $defineListFallback = [];
        if ($mainFile != $fallbackFile) {
            $defineListFallback = $this->loadArrayDefineFile($fallbackFile);
        }
        $defineListMain = $this->loadArrayDefineFile($mainFile);
        $defineList = array_merge($defineListFallback, $defineListMain);
        return $defineList;
    }

    protected function addLanguageDefines($defineList)
    {
        if (!is_array($defineList)) {
            return;
        }
        $newDefineList = array_merge($this->languageDefines, $defineList);
        $this->languageDefines = $newDefineList;
    }

    protected function loadArrayDefineFile($definesFile)
    {
        //echo 'loadArrayDefineFile ' . $definesFile . "<br>";
        $definesList = [];
        if (!is_file($definesFile)) {
            return $definesList;
        }
        $this->mainLoader->addLanguageFilesLoaded('arrays', $definesFile);
        $definesList = include_once($definesFile);
        return $definesList;
    }
}
