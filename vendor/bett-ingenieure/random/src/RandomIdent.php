<?php

/**
 *
 * Copyright (C) 2025, Bett Ingenieure GmbH - All Rights Reserved
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

namespace BettIngenieure\Random;

use BettIngenieure\Base\BaseClass;
use Symfony\Component\Uid\UuidV4;

class RandomIdent extends BaseClass {

    /**
     * @throws \InvalidArgumentException
     */
    public static function getUuid(string $uuid = null) : UuidV4 {
        return new UuidV4($uuid);
    }

    public static function get() : string {
        return self::getUuid()->toRfc4122();
    }

    public static function getBase32() : string {
        return self::getUuid()->toBase32();
    }

    public static function isIdent(string $uuid) : bool {

        try {
            self::getUuid($uuid);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}

