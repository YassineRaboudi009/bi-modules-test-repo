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

namespace BettIngenieure\Base\Serialize;

/**
 *
 * A trait to reduce the serialized result
 * - All properties marked with the Ignore attribute are not included
 * - All properties where the default value equals the current value are not included
 * - Prevent private declared properties to minimize the output
 */
trait OptimizedTrait {

    public function __serialize(): array {

        $cache = ClassPropertiesCache::getInstance()->getFromClassName(static::class);

        $result = [];

        foreach ($cache->reflectionProperties as $identifier => $reflectionProperty) {

            $name = $reflectionProperty->getName();

            if(!$reflectionProperty->isInitialized($this)) {
                continue;
            }

            $value = $reflectionProperty->getValue($this);

            if (
                array_key_exists($identifier, $cache->defaultValues)
                && $value === $cache->defaultValues[$identifier]
            ) {
                continue;
            }

            $key = (
                $reflectionProperty->isPrivate()
                    ? "\0" . $reflectionProperty->getDeclaringClass()->getName() . "\0"
                    : ''
                ) . $name;

            // We must declare exactly, where to place the private value:
            // Scenario:    the original class gets a child class with the same private variable
            // To prevent:  the saved value is restored to the new child class instead as expected to the original class
            // This can't happen to protected or public declared variables

            $result[$key] = $value;
        }

        return $result;
    }
}