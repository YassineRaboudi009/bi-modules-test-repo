<?php
namespace BettIngenieure\FileTest;

use BettIngenieure\File\FileLock;
use BettIngenieure\Tester\BaseTestCase;

class FileLockTest extends BaseTestCase {

    public function test() {

        $lock = new FileLock('TEST1', false);
        $this->assertTrue($lock->lock());
        $this->assertEquals(time(), $lock->getLockTimestamp());

        $lock2 = new FileLock('TEST1', false);
        $this->assertFalse($lock2->lock(), ); // Can't lock, because lock is already set

        $lock->unlock();
    }

    public function testCreateFrom() {

        $lock = FileLock::createFrom(dirname(__FILE__, 2) . '/temp/test-create-from-lock');
        $this->assertTrue($lock->lock());
        $lock->unlock();
    }
}