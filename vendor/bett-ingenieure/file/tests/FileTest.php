<?php

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

namespace BettIngenieure\FileTest;

use BettIngenieure\File\File;
use BettIngenieure\Tester\BaseTestCase;

class FileTest extends BaseTestCase {

    public function getPathToTestFolderAssets() : string {
        return dirname(__FILE__, 2) . '/assets/TestFolder/';
    }

    public function testUserId() : void {

        $file = new File($this->getPathToTestFolderAssets() . 'environmenttestfolder/_empty');

        $processUserId = exec('id -u');

        // start condition
        // user = MacOS Username, group = staff

        $this->assertNotEquals(0, $processUserId);

        $stat = stat($this->getPathToTestFolderAssets() . 'environmenttestfolder/_empty');

        $this->assertEquals($stat['uid'], $file->getOwnerUserId());

        // We can't continue, because we can't change the owner without root permissions
    }

    public function testOwnerId() : void {

        $file = new File($this->getPathToTestFolderAssets() . 'environmenttestfolder/_empty');
        
        $processGroupId = exec('id -g');
        $processGroupName = exec ('id -gn');

        // start condition
        // user = MacOS Username, group = staff

        $this->assertEquals(20, $processGroupId);
        $this->assertEquals('staff', $processGroupName);

        $stat = stat($this->getPathToTestFolderAssets() . 'environmenttestfolder/_empty');

        $this->assertEquals($stat['gid'], $file->getOwnerGroupId());

        $file->setOwnerGroup(12); // 12 = everyone: we need a group, where the current MacOS user is attached to - otherwise, the operation is not allowed as non-root
        $this->assertEquals(12, $file->getOwnerGroupId());

        $file->setOwnerGroup(20);
        $this->assertEquals(20, $file->getOwnerGroupId());
    }

    public function testGetParentDirectoryPath() : void {

        $file = new File($this->getPathToTestFolderAssets() . 'environmenttestfolder/_empty');

        $this->assertEquals($this->getPathToTestFolderAssets() . 'environmenttestfolder/', $file->getParentDirectoryPath());
        $this->assertEquals($this->getPathToTestFolderAssets(), $file->getParentDirectoryPath(2));
    }

    public function testReplaceContentWithinDelimiterEmpty() : void {

        $this->assertEquals(
            '### BEGIN ###' . PHP_EOL . ($newContent = 'TEST2') . PHP_EOL . '### END ###',
            File::replaceContentWithinDelimiter(
                '### BEGIN ###',
                '### END ###',
                '',
                $newContent,
            ),
        );
    }

    public function testReplaceContentWithinDelimiterExists() : void {

        $initial = '### BEGIN ###' . PHP_EOL . 'TEST1' . PHP_EOL . '### END ###';

        $this->assertEquals(
            '### BEGIN ###' . PHP_EOL . ($newContent = 'TEST2') . PHP_EOL . '### END ###',
            File::replaceContentWithinDelimiter('### BEGIN ###', '### END ###', $initial, $newContent),
        );
    }

    public function testReplaceContentWithinDelimiterExistsSurrounded() : void {

        $initial = 'TESTBEGIN ### BEGIN ###' . PHP_EOL . 'TEST1' . PHP_EOL . '### END ###' . PHP_EOL . 'TESTEND';

        $this->assertEquals(
            'TESTBEGIN ### BEGIN ###' . PHP_EOL . ($newContent = 'TEST2') . PHP_EOL . '### END ###' . PHP_EOL . 'TESTEND',
            File::replaceContentWithinDelimiter('### BEGIN ###', '### END ###', $initial, $newContent),
        );
    }

    public function testReplaceContentWithinDuplicatedDelimiterExistsSurrounded() : void {

        $initial = 'TESTBEGIN ### BEGIN ###' . PHP_EOL . 'TEST3' . PHP_EOL . '### END ###' . PHP_EOL . PHP_EOL . '### BEGIN ###' . PHP_EOL . 'TEST1' . PHP_EOL . '### END ###' . PHP_EOL . 'TESTEND';

        $this->assertEquals(
            'TESTBEGIN ' . PHP_EOL . PHP_EOL . '### BEGIN ###' . PHP_EOL . ($newContent = 'TEST2') . PHP_EOL . '### END ###' . PHP_EOL . 'TESTEND',
            File::replaceContentWithinDelimiter('### BEGIN ###', '### END ###', $initial, $newContent),
        );
    }
}