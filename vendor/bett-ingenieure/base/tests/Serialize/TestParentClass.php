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

class TestParentClass extends TestParentSecondClass {

    use TestParentTrait;

    /** @phpstan-ignore-next-line Yes, it is not used */
    private string $privateParentAndChildValue;

    /** @phpstan-ignore-next-line Yes, it is not used */
    protected string $protectedParentAndChildValue;

    /** @phpstan-ignore-next-line Yes, it is not used */
    public string $publicParentAndChildValue;

    /** @phpstan-ignore-next-line Yes, it is not used */
    private string $privateParentValue; // Not initialized

    /** @phpstan-ignore-next-line Yes, it is not used */
    private string $privateParentValueWithDefaultValue = "Parent-Private-Test";

    private string $privateParentValueWithCustomValue;

    public function __construct(
        string $privateValue,
        string $privateTraitValue,
    ) {
        parent::__construct();

        $this->privateParentValueWithCustomValue = $privateValue;
        $this->privateParentTraitValueWithCustomValue = $privateTraitValue;

        $this->privateParentAndChildValue = 'parent-value';
        $this->protectedParentAndChildValue = 'parent-value';
        $this->publicParentAndChildValue = 'parent-value';
    }

    public function getPrivateParentValue() : string {
        return $this->privateParentValueWithCustomValue;
    }

    public function getPrivateParentAndChildValueParent() : string {
        return $this->privateParentAndChildValue;
    }
}