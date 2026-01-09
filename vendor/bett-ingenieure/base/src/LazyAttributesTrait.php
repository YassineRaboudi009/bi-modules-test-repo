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

namespace BettIngenieure\Base;

trait LazyAttributesTrait {
    
    /** @var int[] $__lazyExecuted */
    #[Serialize\Ignore]
    protected array $__lazyExecuted = [];

    protected function _executeLazy(string $name, \Closure $callable) : void {

        if( isset($this->__lazyExecuted[$name]) ) {
            return;
        }

        $this->__lazyExecuted[$name] = 1;
        $callable();
    }

    /** @var array<string,int> $__lazyAttributes */
    #[Serialize\Ignore]
    protected array $__lazyAttributes = [];

    /** @var array<string, mixed> $__lazyAttributesValues  */
    #[Serialize\Ignore]
    protected array $__lazyAttributesValues = [];

    /**
     * @template T
     * @param string $name Name of the variable
     * @param \Closure() : T $valueProducer Callable which returns the value
     * @return T
     */
    protected function _getLazy(string $name, \Closure $valueProducer) : mixed {

        // Protect valueProducer from called in loop
        if( !isset($this->__lazyAttributes[$name]) ) {

            $this->__lazyAttributes[ $name ] = 0;
            $this->__lazyAttributesValues[ $name ] = $valueProducer();
        }

        if(!array_key_exists($name, $this->__lazyAttributesValues)) { // Loop active
            throw new \Exception('Lazy attribute "' . $name . '" has been requested while the value is produced');
        }

        return $this->__lazyAttributesValues[ $name ];
    }

    protected function _setLazyAttribute(string $name, mixed $value) : void {

        $this->__lazyAttributes[ $name ] = 1;
        $this->__lazyAttributesValues[ $name ] = $value;
    }

    protected function _unsetLazyAttribute(string $name) : void {

        unset($this->__lazyAttributes[$name]);
        unset($this->__lazyAttributesValues[$name]);
    }
}