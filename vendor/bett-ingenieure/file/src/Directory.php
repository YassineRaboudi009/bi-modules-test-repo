<?php

/**
 *
 * Copyright (C) 2023, Bett Ingenieure GmbH - All Rights Reserved
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL Bett Ingenieure GmbH BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace BettIngenieure\File;

use BettIngenieure\ORMFramework\Core\Exceptions;

class Directory extends AbstractFileSystemEntry implements DirectoryInterface {

    public function __construct(string $path) {

        if(substr($path, -1) === DIRECTORY_SEPARATOR) {
            $path = substr($path, 0, -1);
        }

        parent::__construct($path);
    }

    public function getPath() : string {
        return $this->source;
    }

    /**
     * @throws \Exception
     */
    public function create(int $permissions = null, bool $recursive = false, int|string $ownerUser = null, int|string $ownerGroup = null, ?string $recursionOption = self::RECURSIVE_OPTION_INHERIT_OWNERSHIP_IF_ROOT) : void {

        if($permissions === null) {
            $permissions = 0755;
        }

        $suppressed = $this->suppressError(function() use($permissions, $recursive, $ownerUser, $ownerGroup, $recursionOption) {

            if (!$this->exists()) {

                if(!($parent = $this->getParentDirectory())->exists()) {
                    $parent->create($permissions, $recursive, $ownerUser, $ownerGroup, $recursionOption);
                }

                $oldMask = umask(0);
                mkdir($this->source, $permissions);
                umask($oldMask);

                if($ownerUser !== null) {
                    $this->setOwnerUser($ownerUser);
                }

                if($ownerGroup !== null) {
                    $this->setOwnerGroup($ownerGroup);
                }

                if(
                    $recursionOption == self::RECURSIVE_OPTION_INHERIT_OWNERSHIP_IF_ROOT
                ) {
                    if($ownerUser === null && $ownerGroup === null) {
                        $this->inheritOwnershipIfRoot();
                    }
                }
                else if($recursionOption == self::RECURSIVE_OPTION_INHERIT_OWNERSHIP_NOT) {}
                else {
                    throw new \RuntimeException('Unknown recursion option: ' . $recursionOption);
                }

            }
        });

        if($suppressed === true) {
            clearstatcache(true, $this->source);
            return;
        }

        if (is_dir($this->source)) {
            return;
        }

        throw new \Exception(sprintf('Not able to create directory "%s".', $this->source));
    }

    public function exists() : bool {
        return parent::exists() && is_dir($this->source);
    }

    public function delete(bool $recursive = false) : void {

        $callable = function(string $path) use(&$callable, $recursive) {

            if($recursive) {
                foreach (scandir($path) as $file) {

                    if ($file == '.' || $file == '..') {
                        continue;
                    }

                    if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                        $callable($path . DIRECTORY_SEPARATOR . $file);
                        continue;
                    }

                    $this->suppressError(fn() => unlink($path . DIRECTORY_SEPARATOR . $file));

                }
            }
            return rmdir($path);
        };

        $callable($this->source);
    }

    private function suppressError(\Closure $callback) : ?bool {

        if(class_exists(Exceptions\Warning::class)) {

            try {
                $callback();
                return false;
            }catch (Exceptions\Warning $e) {
                return true;
            }

        } else {
            $callback();
            return null;
        }
    }

    /**
     * @param null|\Closure(string $filePath) : bool $filter
     * @param bool $recursive
     * @return string[]
     */
    public function list(\Closure $filter = null, bool $recursive = true) : array {

        $result = [];

        foreach (scandir($this->source) as $file) {

            if($file == '.' || $file == '..') {
                continue;
            }

            $filePath = $this->source . DIRECTORY_SEPARATOR . $file;

            if(
                $filter === null
                || $filter($filePath)
            ) {
                $result[] = $filePath;
            }

            if(
                !$recursive
                || !is_dir($filePath)
            ) {
                continue;
            }

            $result = array_merge(
                $result,
                (new static($filePath))->list($filter, $recursive),
            );
        }

        return $result;
    }
}