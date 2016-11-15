<?php
/**
 * Polder Knowledge / PkTool (https://polderknowledge.com)
 *
 * @link https://github.com/polderknowledge/pktool for the canonical source repository
 * @copyright Copyright (c) 2002-2016 Polder Knowledge (https://www.polderknowledge.com)
 * @license https://github.com/polderknowledge/pktool/blob/master/LICENSE.md MIT
 */

namespace PolderKnowledge\PkTool\Utils;

use DirectoryIterator;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class FileSystem
{
    public static function isDirectoryEmpty($path)
    {
        $iterator = new DirectoryIterator($path);

        foreach ($iterator as $entry) {
            if ($entry->isDot()) {
                continue;
            }

            return false;
        }

        return true;
    }

    public static function createDirectory($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    public static function removeDirectory($path)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var SplFileInfo $entry */
        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }

        rmdir($path);
    }
}
