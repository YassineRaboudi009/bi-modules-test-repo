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

namespace BettIngenieure\BaseTest\Serialize;

use BettIngenieure\Base\BaseClass;
use BettIngenieure\Base\LazyAttributesTrait;
use BettIngenieure\Base\Serialize\OptimizedTrait;

class TestClass extends TestParentClass {

    use OptimizedTrait;
    use LazyAttributesTrait;

    /** @phpstan-ignore-next-line Yes, it is not used */
    private string $privateParentAndChildValue;

    /** @phpstan-ignore-next-line Yes, it is not used */
    protected string $protectedParentAndChildValue;

    /** @phpstan-ignore-next-line Yes, it is not used */
    public string $publicParentAndChildValue;

    /** @phpstan-ignore-next-line Yes, it is not used */
    private string $privateValue; // Not initialized

    /** @phpstan-ignore-next-line Yes, it is not used */
    private string $privateValueWithDefaultValue = "Private-Test";

    private string $privateValueWithCustomValue;

    /** @phpstan-ignore-next-line Yes, it is not used */
    protected string $protectedValue; // Not initialized

    /** @phpstan-ignore-next-line Yes, it is not used */
    protected string $protectedValueWithDefaultValue = "Protected-TEST";
    protected string $protectedValueWithCustomValue;

    /** @phpstan-ignore-next-line Yes, it is not used */
    public string $publicValue; // Not initialized

    /** @phpstan-ignore-next-line Yes, it is not used */
    public string $publicValueWithDefaultValue = "Public-TEST";
    public string $publicValueWithCustomValue;

    /** @phpstan-ignore-next-line Yes, it is not used */
    public ?string $nullableValue;

    public function __construct(
        string $privateParentValue,
        string $privateParentTraitValue,
        string $privateValue,
        string $protectedValue,
        string $publicValue,
    ) {
        parent::__construct($privateParentValue, $privateParentTraitValue);

        $this->privateValueWithCustomValue = $privateValue;
        $this->protectedValueWithCustomValue = $protectedValue;
        $this->publicValueWithCustomValue = $publicValue;

        // Test lazy attributes usage
        $this->_getLazy('TEST', fn() => 'test');

        $this->nullableValue = null;

        $this->privateParentAndChildValue = 'child-value';
        $this->protectedParentAndChildValue = 'child-value';
        $this->publicParentAndChildValue = 'child-value';
    }

    public function getPrivateValue() : string {
        return $this->privateValueWithCustomValue;
    }

    public function getProtectedValue() : string {
        return $this->protectedValueWithCustomValue;
    }

    public function getPublicValue() : string {
        return $this->publicValueWithCustomValue;
    }

    public function getPrivateParentAndChildValueChild() : string {
        return $this->privateParentAndChildValue;
    }
}