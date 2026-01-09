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

use BettIngenieure\Base\BaseClass;

class FileLock extends BaseClass {

    protected bool $_shouldBlock;
    protected string $_filePath;

    public function __construct(string $fileName, bool $shouldBlock = true, string $path = null) {

        $this->_shouldBlock = $shouldBlock;

        if($path === null) {
            $path = $this->getLockPath();
        }

        if(strrpos($path, DIRECTORY_SEPARATOR, -1) !== strlen($path) - 1) {
            $path = $path . DIRECTORY_SEPARATOR;
        }

        $directory = new Directory($path);
        if(!$directory->exists()) {
            $directory->create(null, true);
        }

        $this->_filePath = $path . $fileName;
    }

    public static function createFrom(string $filePath, bool $shouldBlock = true) : self {

        return new self(
            basename($filePath),
            $shouldBlock,
            dirname($filePath),
        );
    }

    private static ?string $lockPath = null;

    public static function setLockPath(string $lockPath = null) : void {
        self::$lockPath = $lockPath;
    }

    protected function getLockPath() : string {

        if(self::$lockPath !== null) {
            return self::$lockPath;
        }

        if(!isset($_SERVER['TMPDIR'])) {
            throw new \RuntimeException('TMPDIR not defined');
        }

        return $_SERVER['TMPDIR'] . 'locks' . DIRECTORY_SEPARATOR;
    }

    protected $_handle = null;

    /**
     * @return bool TRUE on lock obtained, FALSE if !$shouldBlock and lock is set by another process
     */
    public function lock() : bool {

        if(!file_exists($this->_filePath)) {
            (new File($this->_filePath))->write('');
        }

        $this->_handle = fopen($this->_filePath, 'r');

        if( !flock(
            $this->_handle,
            $this->_shouldBlock ? LOCK_EX : LOCK_EX | LOCK_NB,
            $wouldBlock
            )
        ) {

            if( $wouldBlock ) {

                // another process holds the lock
                if( !$this->_shouldBlock ) {
                    return false;
                }
            }

            throw new \RuntimeException('Could not lock');

        } else {

            // lock obtained

            touch($this->_filePath);
            return true;
        }
    }

    public function getLockTimestamp() : ?int {

        if(file_exists($this->_filePath)) {
            return filemtime($this->_filePath);
        }
        return null;
    }

    public function unlock() : void {

        flock($this->_handle, LOCK_UN);
        fclose($this->_handle);
    }
}