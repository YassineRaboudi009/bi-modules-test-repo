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

namespace BettIngenieure\UtilitiesTest;

use BettIngenieure\Tester\BaseTestCase;
use BettIngenieure\Utilities\Interpolator;

class InterpolatorTest extends BaseTestCase {

    public function test() {

        $interpolator = new Interpolator();

        $this->assertEquals($subject = 'test "$16-177"', $interpolator->do($subject, []));

        $this->assertEquals($subject = '$100.00', $interpolator->do('?', [$subject]));
        $this->assertEquals($subject = '$100.00\12 TEST', $interpolator->do('?', [$subject]));
        $this->assertEquals('TEST TEST2', $interpolator->do('? ?', ['TEST', 'TEST2']));
        $this->assertEquals('TEST TEST2', $interpolator->do('? :KEY', ['TEST', 'KEY' =>'TEST2']));

        $this->assertEquals('TEST TEST2', $interpolator->do('? ::KEY', ['TEST', ':KEY' =>'TEST2']));
        $this->assertEquals('TEST TEST2', $interpolator->do('? :KEY\e', ['TEST', 'KEY\e' =>'TEST2'])); // Test preg key quoting

        $this->catchExpectedException(\RuntimeException::class, function() use($interpolator) {
            $interpolator->do('?', ['TEST', 'KEY' =>'TEST2']); // Test missing placeholder
        });

        // TODO
        //$this->catchExpectedException(\RuntimeException::class, function() use($interpolator) {
        //    $interpolator->do('? ?', ['TEST']); // Test missing value
        //});

        $this->assertEquals('TEST ?', $interpolator->do('? \?', ['TEST']));
    }

    public function testEscaping() {

        $interpolator = new Interpolator();

        $this->assertEquals('"test "$16-177""', $interpolator->do('?', ['test "$16-177"'], fn(string $v) => '"' . $v . '"'));
    }

    public function testEscapePlaceholder() {

        $interpolator = new Interpolator();

        $this->catchExpectedException(\RuntimeException::class, function() use($interpolator) {
            $interpolator->do($interpolator->escapePlaceholder('?'), ['TEST']);
        });

        $this->assertEquals('?', $interpolator->do($interpolator->escapePlaceholder('?'), []));
    }

    public function testEscapeNamedPlaceholder() {

        $interpolator = new Interpolator();

        $this->catchExpectedException(\RuntimeException::class, function() use($interpolator) {
            $interpolator->do($interpolator->escapePlaceholder(':KEY', $values = ['KEY' => 'TEST']), $values);
        });

        $this->assertEquals(':KEY', $interpolator->do($interpolator->escapePlaceholder(':KEY'), []));
    }

    public function testEscapePlaceholderWithinValues() {

        $interpolator = new Interpolator();

        $this->assertEquals('TEST :KEY2 TEST2', $interpolator->do(':KEY :KEY2', ['KEY' =>'TEST :KEY2', 'KEY2' =>'TEST2']));

        $this->assertEquals('?test test2', $interpolator->do('? ?', ['?test', 'test2']));
    }
}