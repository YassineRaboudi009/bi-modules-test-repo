<?php
namespace BettIngenieure\BaseTest;

use BettIngenieure\Base\BaseClass;
use BettIngenieure\Tester\BaseTestCase;

class BaseClassTest extends BaseTestCase {

    public function test() {

        $test = new BaseClass();

        // Unknown attribute (get)
        $this->catchExpectedException(\RuntimeException::class, function() use($test) {
            /** @phpstan-ignore-next-line */
            $test->test;
        });

        // Unknown attribute (set)
        $this->catchExpectedException(\RuntimeException::class, function() use($test) {
            /** @phpstan-ignore-next-line */
            $test->test = '1';
        });

        // Unknown function
        $this->catchExpectedException(\RuntimeException::class, function() use($test) {
            /** @phpstan-ignore-next-line */
            $test->test('1');
        });

    }
}