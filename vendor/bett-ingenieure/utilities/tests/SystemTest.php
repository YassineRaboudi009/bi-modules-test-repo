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
use BettIngenieure\Utilities\Exceptions\Run;
use BettIngenieure\Utilities\System;

class SystemTest extends BaseTestCase {

    public function testRunSuccess() : void {

        $this->assertEquals('TEST', System::getInstance()->run(['echo', 'TEST']));
    }

    public function testArgumentInjection() : void {

        $this->assertEquals(
            'TEST' . PHP_EOL,
            System::getInstance()->run(['echo', 'TEST'], null, null, null, 60, false),
            'By default there is a trailing new line character',
        );

        $this->assertEquals(
            'TEST',
            System::getInstance()->run(['echo', '-n', 'TEST'], null, null, null, 60, false),
            '-n removes the trailing new line character',
        );

        $this->assertEquals(
            '-n TEST' . PHP_EOL,
            System::getInstance()->run(['echo', '-n TEST'], null, null, null, 60, false),
            'Test is passed, if the trailing new lien character is still there'
        );
    }

    public function testOnBashShellArgumentInjection() : void {

        $this->assertEquals(
            'TEST' . PHP_EOL,
            System::getInstance()->runOnBashShell(['echo', 'TEST'], null, null, null, 60, false),
            'By default there is a trailing new line character',
        );

        $this->assertEquals(
            'TEST',
            System::getInstance()->runOnBashShell(['echo', '-n', 'TEST'], null, null, null, 60, false),
            '-n removes the trailing new line character',
        );

        $this->assertEquals(
            '-n TEST' . PHP_EOL,
            System::getInstance()->runOnBashShell(['echo', '-n TEST'], null, null, null, 60, false),
            'Test is passed, if the trailing new lien character is still there'
        );
    }

    public function testOnBashShellWithPlaceholderArgumentInjection() : void {

        $this->assertEquals(
            'TEST' . PHP_EOL,
            System::getInstance()->runOnBashShellWithPlaceholder('echo ?', ['TEST'], null, null, null, 60, false),
            'By default there is a trailing new line character',
        );

        $this->assertEquals(
            '-n TEST' . PHP_EOL,
            System::getInstance()->runOnBashShellWithPlaceholder('echo ?', ['-n TEST'], null, null, null, 60, false),
            'Test is passed, if the trailing new lien character is still there',
        );
    }

    public function testOnBashShellCombinedCommands() : void {

        $this->assertStringContainsString(
            'SystemTest.php' . PHP_EOL,
            System::getInstance()->runOnBashShellWithPlaceholder('cd . ; ls', [], null, __DIR__, null, 60, false),
        );

        $this->assertStringContainsString(
            'composer.json' . PHP_EOL,
            System::getInstance()->runOnBashShellWithPlaceholder('cd .. ; ls', [], null, __DIR__, null, 60, false),
        );
    }

    public function testRunFailure() : void {

        $this->catchExpectedException(Run::class, function() {
            System::getInstance()->run(['ls', 'not-found']);
        });
    }
}