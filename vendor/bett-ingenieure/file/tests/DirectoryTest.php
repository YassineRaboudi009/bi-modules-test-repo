<?php
namespace BettIngenieure\FileTest;

/**
 *
 * Copyright (C) 2023, Bett Ingenieure GmbH - All Rights Reserved
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

use BettIngenieure\File\Directory;
use BettIngenieure\File\FileLock;
use BettIngenieure\Tester\BaseTestCase;

class DirectoryTest extends BaseTestCase {

    private function getTempDir() : string {

        if(!isset($_SERVER['TMPDIR'])) {
            throw new \RuntimeException('TMPDIR not defined');
        }

        return $_SERVER['TMPDIR'];
    }

    public function testGetPath() : void {

        $directory = new Directory($this->getTempDir() . 'test');
        $this->assertTrue($directory->getPath() == $this->getTempDir() . 'test');

        $directory = new Directory($this->getTempDir() . 'test' . DIRECTORY_SEPARATOR);
        $this->assertTrue($directory->getPath() == $this->getTempDir() . 'test');
    }

    public function testLocked() : void {

        $lock = new FileLock('test');
        $lock->lock();

        $this->testLockedExists();
        $this->testLockedCreate();
        $this->testLockedCreateSpreadOwnership();
        $this->testLockedDelete();

        $lock->unlock();
    }

    private function testLockedExists() : void {

        $directory = new Directory($this->getTempDir() . 'test' . DIRECTORY_SEPARATOR . 'test2');
        $this->assertFalse($directory->exists(), $directory->getPath());

        $directory->create(null, true);
        $this->assertTrue($directory->exists(), $directory->getPath());
    }

    private function testLockedCreate() : void {

        $directory = new Directory($this->getTempDir() . 'test' . DIRECTORY_SEPARATOR . 'test3');
        $this->assertTrue(!$directory->exists());

        $directory->create();
        $this->assertTrue($directory->exists());

        $directory->delete();
        $this->assertTrue(!$directory->exists());
    }

    private function testLockedCreateSpreadOwnership() : void {

        // Preparation
        $directory = new Directory($this->getTempDir() . 'test' . DIRECTORY_SEPARATOR . 'test3' . DIRECTORY_SEPARATOR . 'test4');
        $this->assertTrue(!$directory->exists());
        $this->assertTrue(!$directory->getParentDirectory()->exists());

        // Create test directory
        $directory->create(null, true);
        $this->assertTrue($directory->exists());

        // Validate default group id is not everyone, the test group
        $this->assertNotEquals(12, $directory->getOwnerGroupId()); // 12 = everyone (macOS)
        $this->assertNotEquals(12, $directory->getParentDirectory()->getOwnerGroupId());

        // Clean up
        $directory->getParentDirectory()->delete(true);
        $this->assertTrue(!$directory->getParentDirectory()->exists());

        // Create test directory
        $directory->create(null, true, null, 12);
        $this->assertTrue($directory->exists());

        // Validate spread of test owner group
        $this->assertEquals(12, $directory->getOwnerGroupId()); // 12 = everyone (macOS)
        $this->assertEquals(12, $directory->getParentDirectory()->getOwnerGroupId());

        // Clean up
        $directory->getParentDirectory()->delete(true);
        $this->assertTrue(!$directory->getParentDirectory()->exists());
    }

    private function testLockedDelete() : void {

        $directory = new Directory($this->getTempDir() . 'test');
        $this->assertTrue($directory->exists());
        $directory->delete(true);
    }

    public function getPathToTestFolderAssets() : string {
        return dirname(__FILE__, 2) . '/assets/TestFolder/';
    }

    public function testList() : void {

        $directory = new Directory($this->getPathToTestFolderAssets() . 'environmenttestfolder');

        $result = $directory->list();
        $this->assertCount(4, $result);
        $this->assertEquals($this->getPathToTestFolderAssets() . 'environmenttestfolder/_empty', $result[0]);
        $this->assertEquals($this->getPathToTestFolderAssets() . 'environmenttestfolder/environmenttest2', $result[1]);
        $this->assertEquals($this->getPathToTestFolderAssets() . 'environmenttestfolder/environmenttest2/_empty', $result[2]);
        $this->assertEquals($this->getPathToTestFolderAssets() . 'environmenttestfolder/environmenttest2/_empty2', $result[3]);
    }

    public function testFilteredList() : void {

        $directory = new Directory($this->getPathToTestFolderAssets() . 'environmenttestfolder');

        $result = $directory->list(fn(string $filePath) => str_contains($filePath, 'empty'));
        $this->assertCount(3, $result);
        $this->assertEquals($this->getPathToTestFolderAssets() . 'environmenttestfolder/_empty', $result[0]);
        $this->assertEquals($this->getPathToTestFolderAssets() . 'environmenttestfolder/environmenttest2/_empty', $result[1]);
        $this->assertEquals($this->getPathToTestFolderAssets() . 'environmenttestfolder/environmenttest2/_empty2', $result[2]);
    }

    public function testGetOwnerId() : void {

        $directory = new Directory($this->getPathToTestFolderAssets() . 'environmenttestfolder');

        $stat = stat($this->getPathToTestFolderAssets() . 'environmenttestfolder');

        $this->assertEquals($stat['uid'], $directory->getOwnerUserId());
        $this->assertEquals($stat['gid'], $directory->getOwnerGroupId());
    }

    public function testGetParentDirectoryPath() : void {

        $directory = new Directory($this->getPathToTestFolderAssets() . 'environmenttestfolder');

        $this->assertEquals($this->getPathToTestFolderAssets(), $directory->getParentDirectoryPath());
        $this->assertEquals(dirname($this->getPathToTestFolderAssets()) . '/', $directory->getParentDirectoryPath(2));
    }
}