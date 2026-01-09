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

use BettIngenieure\Base\BaseClass;
use BettIngenieure\Base\LazyAttributesTrait;
use BettIngenieure\Base\SingletonTrait;

class ClassPropertiesCache extends BaseClass {

    use SingletonTrait;
    use LazyAttributesTrait;

    public function getFromClassName(string $className) : ClassCache {
        return $this->getFromReflectionClass(
            new \ReflectionClass($className)
        );
    }

    public function getFromReflectionClass(\ReflectionClass $reflectionClass) : ClassCache {
        return $this->_getLazy($reflectionClass->getName(), function() use($reflectionClass) : ClassCache {

            $defaultValues = [];
            $reflectionProperties = [];

            if($reflectionClass->getParentClass() !== false) {
                $parentResult = $this->getFromReflectionClass($reflectionClass->getParentClass());
                $defaultValues = $parentResult->defaultValues;
                $reflectionProperties = $parentResult->reflectionProperties;
            }

            foreach($reflectionClass->getDefaultProperties() as $propertyName => $value) {
                $defaultValues[$reflectionClass->getName() . '::' . $propertyName] = $value;
            }

            foreach ($reflectionClass->getProperties() as $reflectionProperty) {

                if ($reflectionProperty->getAttributes(Ignore::class)) {
                    continue;
                }

                $reflectionProperties[$reflectionClass->getName() . '::' . $reflectionProperty->getName()] = $reflectionProperty;
            }

            return new ClassCache(
                $defaultValues,
                $reflectionProperties,
            );
        });
    }
}