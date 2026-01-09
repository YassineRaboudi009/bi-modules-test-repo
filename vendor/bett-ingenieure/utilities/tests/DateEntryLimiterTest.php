<?php

/*
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

namespace BettIngenieure\UtilitiesTest;

use BettIngenieure\Tester\BaseTestCase;
use BettIngenieure\Utilities\DateEntryLimiter;
use BettIngenieure\Utilities\Struct\DateEntry;

class DateEntryLimiterTest extends BaseTestCase {

    public function testAddDaysToKeep() {

        $limiter = new DateEntryLimiter();
        $limiter->addDaysToKeep(2);

        $datesToKeep = $limiter->getDatesToKeep();
        $this->assertCount( 3, $datesToKeep);
        $this->assertEquals(strtotime('00:00:00'), $datesToKeep[0]);
        $this->assertEquals(strtotime('-1 days 00:00:00'), $datesToKeep[1]);
        $this->assertEquals(strtotime('-2 days 00:00:00'), $datesToKeep[2]);
    }

    public function testAddDaysToKeepInterval() {

        $interval = 2;

        $limiter = new DateEntryLimiter();
        $limiter->addDaysToKeep(2, $interval);

        $datesToKeep = $limiter->getDatesToKeep();
        $this->assertCount( 3, $datesToKeep);
        $this->assertEquals(strtotime('00:00:00'), $datesToKeep[0]);
        $this->assertEquals(strtotime('-' . (1*$interval) . ' days 00:00:00'), $datesToKeep[1]);
        $this->assertEquals(strtotime('-' . (2*$interval) . ' days 00:00:00'), $datesToKeep[2]);
    }

    public function testAddWeeksToKeep() {

        $limiter = new DateEntryLimiter();
        $limiter->addWeeksToKeep(2);

        $datesToKeep = $limiter->getDatesToKeep();
        $this->assertCount( 4, $datesToKeep);

        $firstTick = strtotime('sunday this week', strtotime('00:00:00'));
        if($firstTick > time()) {
            $firstTick = strtotime('00:00:00');
        }

        $this->assertEquals($firstTick, $datesToKeep[0]);
        $this->assertEquals(strtotime('sunday this week', strtotime('-1 weeks 00:00:00')), $datesToKeep[1]);
        $this->assertEquals(strtotime('sunday this week', strtotime('-2 weeks 00:00:00')), $datesToKeep[2]);
    }

    public function testAddWeeksToKeepInterval() {

        $interval = 2;

        $limiter = new DateEntryLimiter();
        $limiter->addWeeksToKeep(2, $interval);

        $datesToKeep = $limiter->getDatesToKeep();
        $this->assertCount( 4, $datesToKeep);

        $firstTick = strtotime('sunday this week', strtotime('00:00:00'));
        if($firstTick > time()) {
            $firstTick = strtotime('00:00:00');
        }

        $this->assertEquals($firstTick, $datesToKeep[0]);
        $this->assertEquals(strtotime('sunday this week', strtotime('-' . (1*$interval) . ' weeks 00:00:00')), $datesToKeep[1]);
        $this->assertEquals(strtotime('sunday this week', strtotime('-' . (2*$interval) . ' weeks 00:00:00')), $datesToKeep[2]);
    }

    public function testAddMonthsToKeep() {

        $limiter = new DateEntryLimiter();
        $limiter->addMonthsToKeep(2);

        $daysToKeep = $limiter->getDatesToKeep();

        $this->assertCount(4, $daysToKeep);
        $this->assertEquals(strtotime('1.' . date("m.Y", strtotime('00:00:00')) . ' 00:00:00'), $daysToKeep[0]);
        $this->assertEquals(strtotime('1.' . date("m.Y", strtotime('-1 months 00:00:00')) . ' 00:00:00'), $daysToKeep[1]);
        $this->assertEquals(strtotime('1.' . date("m.Y", strtotime('-2 months 00:00:00')) . ' 00:00:00'), $daysToKeep[2]);
    }

    public function testAddMonthsToKeepInterval() {

        $limiter = new DateEntryLimiter();
        $limiter->addMonthsToKeep(2, 2);

        $daysToKeep = $limiter->getDatesToKeep();

        $this->assertCount(4, $daysToKeep);
        $this->assertEquals(strtotime('1.' . date("m.Y", strtotime('00:00:00')) . ' 00:00:00'), $daysToKeep[0]);
        $this->assertEquals(strtotime('1.' . date("m.Y", strtotime('-2 months 00:00:00')) . ' 00:00:00'), $daysToKeep[1]);
        $this->assertEquals(strtotime('1.' . date("m.Y", strtotime('-4 months 00:00:00')) . ' 00:00:00'), $daysToKeep[2]);
    }

    private function testGetOutdatedEntries(\Closure $preset, string $unit) : void {

        for($i = 1; $i < 3; $i++) {
            for($d = 1; $d < 10; $d++) {
                $this->testGetOutdatedEntriesFrom($preset, $unit, $d, $i);
            }
        }
    }

    private function testGetOutdatedEntriesFrom(\Closure $preset, string $unit, int $amount, int $interval) : void {

        $reference = strtotime('03.06.2022');
        $firstTick = strtotime('+' . $amount*$interval . ' ' . $unit, $reference);
        $dateEntries = [];

        for($i = 0; $i < 3000; $i++) {

            $limiter = new DateEntryLimiter($current = strtotime('+' . $i . ' days' , $reference));
            $preset($limiter, $amount, $interval);

            $outdated = $limiter->getOutdatedEntries($dateEntries);
            $this->assertCount( $amount + ($unit !== 'days' ? 2 : 1),  $limiter->getDatesToKeep());

            foreach($outdated as $dataEntry) {
                unset($dateEntries[array_search($dataEntry, $dateEntries, true)]);
            }
            $dateEntries = array_values($dateEntries); // set new keys

            if($current <= $firstTick) {
            } else {
                if($unit === 'days') {
                    $this->assertCount($amount + 1, $dateEntries);
                } else {
                    $this->assertTrue(count($dateEntries) > $amount && count($dateEntries) <= $amount + 1 + 1, (string)count($dateEntries));
                }
            }

            foreach($dateEntries as $dateEntry) {
                $this->assertTrue(strtotime('+' . (($amount+3)*$interval) . ' ' . $unit, $dateEntry->timestamp) > $current); // No old entries
            }

            $dateEntries[] = new DateEntry(strtotime('+' . ($i+1) . ' days', $reference), null);
        }
    }

    public function testGetOutdatedEntriesDays() {

        $this->testGetOutdatedEntries(
            fn(DateEntryLimiter $o, int $amount, int $interval) => $o->addDaysToKeep($amount, $interval),
            'days',
        );
    }

    public function testGetOutdatedEntriesWeeks() {

        $this->testGetOutdatedEntries(
            fn(DateEntryLimiter $o, int $amount, int $interval) => $o->addWeeksToKeep($amount, $interval),
            'weeks',
        );
    }

    public function testGetOutdatedEntriesMonths() {

        $this->testGetOutdatedEntries(
            fn(DateEntryLimiter $o, int $amount, int $interval) => $o->addMonthsToKeep($amount, $interval),
            'months',
        );
    }
}