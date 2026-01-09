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

namespace BettIngenieure\Utilities;

use BettIngenieure\Base\BaseClass;

class DateEntryLimiter extends BaseClass {

    /** @var array<array-key, ?Struct\DateEntry> $datesToKeep */
    private array $datesToKeep = [];
    private int $referenceTimestamp;

    private bool $echoLog = false;

    public function __construct(int $referenceTimestamp = null) {

        $this->referenceTimestamp = $referenceTimestamp ?? time();
    }

    public function setEchoLog(bool $enabled) : void {
        $this->echoLog = $enabled;
    }

    private function log(string $message) : void {

        if(!$this->echoLog) {
            return;
        }

        echo $message . PHP_EOL;
    }

    public function addDaysToKeep(int $days, int $interval = 1) : void {

        if($interval < 1) {
            throw new \RuntimeException('Interval must be in range >= 1');
        }

        for($i = 0; $i < ($days+1)*$interval; ) {

            $this->datesToKeep[strtotime("-" . $i . ' days 00:00:00', $this->referenceTimestamp)] = null;

            $i += $interval;
        }
    }

    public function addWeeksToKeep(int $weeks, int $interval = 1) : void {

        if($interval < 1) {
            throw new \RuntimeException('Interval must be in range >= 1');
        }

        for($i = 0; $i < ($weeks+2)*$interval; ) { // non-continues units need one additional tick to satisfy the expected amount

            $timestamp = strtotime(
                'sunday this week 00:00:00',
                strtotime("-" . $i . ' weeks', $this->referenceTimestamp),
            );
            if($timestamp > $this->referenceTimestamp) {
                $timestamp = strtotime('00:00:00', $this->referenceTimestamp);
            }

            $this->datesToKeep[$timestamp] = null;

            $i += $interval;
        }
    }

    public function addMonthsToKeep(int $months, int $interval = 1) : void {

        if($interval < 1) {
            throw new \RuntimeException('Interval must be in range >= 1');
        }

        for($i = 0; $i < ($months+2)*$interval; ) {  // non-continues units need one additional tick to satisfy the expected amount
            $this->datesToKeep[strtotime(
                'first day of this month 00:00:00',
                strtotime(
                    "-" . $i . ' months',
                    strtotime('first day of this month 00:00:00', $this->referenceTimestamp),
                ),
            )] = null;

            $i += $interval;
        }
    }

    public function getDatesToKeep() : array {
        return array_keys($this->datesToKeep);
    }

    /**
     * @param array<array-key, Struct\DateEntry> $dateEntries
     * @return array<array-key, Struct\DateEntry> Outdated DateEntries from days NOT to keep
     */
    public function getOutdatedEntries(array $dateEntries) : array {

        $timestamps = array_map(fn(Struct\DateEntry $o) => $o->timestamp, $dateEntries);

        array_multisort(
            $timestamps, SORT_NUMERIC, SORT_ASC,
            $dateEntries,
        );

        $this->log(PHP_EOL . PHP_EOL . 'Reference-Timestamp: ' . date("d.m.Y", $this->referenceTimestamp));

        foreach($this->datesToKeep as $timestamp => $dateEntryToKeep) {

            foreach($dateEntries as $dateEntry) {

                if(
                    $dateEntry->timestamp >= $timestamp
                    && (
                        !isset($this->datesToKeep[$timestamp])
                        || abs($dateEntry->timestamp - $timestamp) < abs($this->datesToKeep[$timestamp]->timestamp - $timestamp)
                    )
                ) {
                    $this->datesToKeep[$timestamp] = $dateEntry; 
                }
            }
        }

        foreach($this->datesToKeep as $timestamp => $dateEntryToKeep) {
            $this->log('Day to keep: ' . date("d.m.Y H:i", $timestamp));
            
            if($dateEntryToKeep === null) {
                continue;
            }

            $this->log('- Assigned to: ' . '(' . date("d.m.Y H:i", $dateEntryToKeep->timestamp) . ') ');
        }

        $result = [];

        foreach( $dateEntries as $dateEntry ) {

            foreach($this->datesToKeep as $timestamp => $dateEntryToKeep) {
                if($dateEntryToKeep === $dateEntry) {
                    continue 2;
                }
            }

            $this->log("Should delete " . '(' . date("d.m.Y H:i", $dateEntry->timestamp) . ') ');

            $result[] = $dateEntry;
        }

        return $result;
    }
}