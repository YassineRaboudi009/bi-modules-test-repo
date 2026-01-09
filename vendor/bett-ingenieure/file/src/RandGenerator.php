<?php
namespace BettIngenieure\File;

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

use BettIngenieure\Base\BaseClass;
use BettIngenieure\Random\RandomHash;

class RandGenerator extends BaseClass {

    public static function getNewFilename(string $directory, string $extensionWithoutDot = "") : string {

        if(substr($directory, -1) !== DIRECTORY_SEPARATOR) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        while (true) {

            $hash = RandomHash::get(64);

            $filename = $hash . ($extensionWithoutDot != "" ? "." . $extensionWithoutDot : "");

            if(file_exists($directory . $filename)) {
                continue;
            }

            return $filename;
        }
    }

    public static function getNewFilePath(string $directory, string $extensionWithoutDot = "") : string {
        return $directory . static::getNewFilename($directory, $extensionWithoutDot);
    }
}

