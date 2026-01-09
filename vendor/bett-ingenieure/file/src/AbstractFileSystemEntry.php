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

abstract class AbstractFileSystemEntry extends BaseClass {

    const RECURSIVE_OPTION_INHERIT_OWNERSHIP_IF_ROOT = 'INHERIT_OWNERSHIP_IF_ROOT';
    const RECURSIVE_OPTION_INHERIT_OWNERSHIP_NOT = null;

    protected string $source;

    public function __construct(string $source) {
        $this->source = $source;
    }

    public function exists() : bool {
        return file_exists($this->source);
    }

    /**
     * Returns the owner's user id. On Windows this will always be 0.
     */
    public function getOwnerUserId() : int {

        if(!file_exists($this->source)) {
            throw new \RuntimeException('Source "' . $this->source . '" not found');
        }

        return stat($this->source)['uid'];
    }

    /**
     * Returns the owner's group id. On Windows this will always be 0.
     */
    public function getOwnerGroupId() : int {

        if(!file_exists($this->source)) {
            throw new \RuntimeException('Source "' . $this->source . '" not found');
        }

        return stat($this->source)['gid'];
    }

    public function setOwnerUser(string|int $user) : void {

        if(!file_exists($this->source)) {
            throw new \RuntimeException('Source "' . $this->source . '" not found');
        }

        chown($this->source, $user);
        clearstatcache(true, $this->source);
    }

    public function setOwnerGroup(string|int $group) : void {

        if(!file_exists($this->source)) {
            throw new \RuntimeException('Source "' . $this->source . '" not found');
        }

        chgrp($this->source, $group);
        clearstatcache(true, $this->source);
    }

    public function getParentDirectoryPath(int $levels = 1) : string {
        return dirname($this->source, $levels) . '/';
    }

    public function getParentDirectory(int $levels = 1) : Directory {
        return new Directory($this->getParentDirectoryPath($levels));
    }

    /**
     * Inherit ownership, when the current process user is root
     */
    public function inheritOwnershipIfRoot() : void {

        if(($_SERVER['USER'] ?? null) !== 'root') {
            return;
        }

        $parent = $this->getParentDirectory();
        $parent->copyOwnershipTo($this);
    }

    public function copyOwnershipTo(AbstractFileSystemEntry $target) : void {

        if(($userId = $this->getOwnerUserId()) !== 0) {
            $target->setOwnerUser($userId);
        }

        if(($groupId = $this->getOwnerGroupId()) !== 0) {
            $target->setOwnerGroup($groupId);
        }
    }
}