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

namespace BettIngenieure\Tester;

use BettIngenieure\Base\BaseClass;
use BettIngenieure\Utilities;
use Swoole;
use Symfony\Component\Console;

class PhpTester extends BaseClass {

    public function __construct(
        private readonly string  $basePath,
        private readonly ?string $selectedVersion = null,
    )
    {}

    public function run(): void {
        $this->invokeProcesses();
    }

    private function invokeProcesses(): void {

        $fileLock = new \BettIngenieure\File\FileLock('tester', true);
        $fileLock->lock();

        $start = microtime(true);

        $result = [];

        $process = new Swoole\Process(function (Swoole\Process $process) {

            if(!defined('PHP_BINARY')) {
                throw new \RuntimeException('PHP\'s constant PHP_BINARY is not set');
            }

            $phpBinaryFilePath = PHP_BINARY;

            $phpVersions = match (true) {
                str_starts_with($phpBinaryFilePath, '/opt/php-') => [
                    '/opt/php-82/current-live/bin/php' => "8.2",
                    '/opt/php-83/current-live/bin/php' => "8.3",
                ],
                str_starts_with($phpBinaryFilePath, '/opt/local/bin/php') => [
                    '/opt/local/bin/php82' => "8.2",
                    '/opt/local/bin/php83' => "8.3",
                ],
                str_starts_with($phpBinaryFilePath, '/opt/homebrew/Cellar/') => [
                    '/opt/homebrew/opt/php@8.2/bin/php' => "8.2",
                    '/opt/homebrew/opt/php@8.3/bin/php' => "8.3",
                ],
                default => throw new \RuntimeException('Failed to identify path bin folders from: ' . $phpBinaryFilePath),
            };

            $shouldClearPhpstanCache = false;
            if (file_exists($cacheFlagFilePath = $_ENV['PWD'] . '/phpstan-clear-cache')) {
                $shouldClearPhpstanCache = true;
                unlink($cacheFlagFilePath);
            }

            $processes = [];


            $executed = false;

            $index = 0;

            foreach ($phpVersions as $executable => $version) {

                if (
                    $this->selectedVersion !== null
                    && $this->selectedVersion !== $version
                ) {
                    continue;
                }

                $executed = true;

                $processes[] = $this->invokeProcess($process, (string)$index++, function () use ($version, $executable, $shouldClearPhpstanCache): string {
                    return $this->runPhpStan($version, $executable, $shouldClearPhpstanCache, $this->basePath);
                });

                $processes[] = $this->invokeProcess($process, (string)$index++, function () use ($executable): string {
                    return $this->runPhpUnit($executable, $this->basePath);
                });
            }

            if (!$executed) {
                throw new \RuntimeException('The selected version is not available');
            }

            foreach ($processes as $pid) {
                Swoole\Process::wait();
            }
        });

        $process->start();
        $process->setBlocking(false);
        Swoole\Process::wait();

        while (($feedback = @$process->read()) !== false) {

            if ($feedback === 'ERROR') {
                die(1);
            }

            try {
                $feedbackArray = unserialize($feedback);
            } catch (\Exception $e) {
                echo "FAILED TO UNSERIALIZE THE FEEDBACK" . PHP_EOL;
                var_dump($feedback);
                die(1);
            }

            foreach ($feedbackArray as $key => $value) {
                $result[$key] = $value;
            }
        }

        ksort($result);

        foreach (array_keys($result) as $key) {
            echo $result[$key] . PHP_EOL;
        }

        echo 'PHPTester: End reached after: ' . round(microtime(true) - $start, 2) . 's';
    }

    private function invokeProcess(Swoole\Process $parent, string $resultKey, \Closure $callable) : int {

        $process = new Swoole\Process(function() use($parent, $resultKey, $callable) {

            try {
                $result = $callable();
                $parent->write(serialize([$resultKey => $result]));
            } catch (\Exception $e) {
                echo $e->getMessage() . PHP_EOL;
                $parent->write('ERROR');
                @Swoole\Process::kill($parent->pid, 1);
            }
        });
        $process->useQueue();
        $process->setTimeout(120);

        return $process->start();
    }

    /**
     * @throws \Exception
     */
    private static function runPhpStan(string $version, string $executable, bool $clearCache, string $basePath): string {

        $start = microtime(true);

        $configFile = 'phpstan-php' . $version . '.neon';
        if (!file_exists($_ENV['PWD'] . '/' . $configFile)) {
            symlink(dirname(__FILE__, 2) . '/' . $configFile, $_ENV['PWD'] . '/' . $configFile);
        }

        $header = 'PHPStan - PHP executable: ' . $executable . ' (' . $version . ')';

        $result = "";

        if ($clearCache) {
            try {
                $result .= Utilities\System::getInstance()->run(
                    [$executable, $basePath . '/vendor/bin/phpstan', 'clear-result-cache', '--ansi', '--memory-limit', '2G', '-c', $configFile]
                );
            } catch (Utilities\Exceptions\Run $e) {
                throw new \Exception(
                    self::formatErrorMessage($header . ' - clear-cache failed!') . PHP_EOL .
                    $e->getOutput(),
                );
            }
            $result .= PHP_EOL;
        }

        try {
            $result .= Utilities\System::getInstance()->run(
                [$executable, $basePath . '/vendor/bin/phpstan', 'analyse', '--ansi', '--no-progress', '--memory-limit', '2G', '-c', $configFile]
            );
        } catch (Utilities\Exceptions\Run $e) {
            throw new \Exception(
                self::formatErrorMessage($header . ' - analyse failed!') . PHP_EOL .
                $e->getOutput(),
            );
        }
        $result .= PHP_EOL;

        $result .=  self::formatSuccessMessage($header . ' - done after ' . round(microtime(true) - $start, 3) . 's') . PHP_EOL;
        return $result;
    }

    /**
     * @throws \Exception
     */
    private static function runPhpUnit(string $executable, string $basePath): string {

        $start = microtime(true);

        $header = 'PHPUnit - PHP executable: ' . $executable;

        $output = "";

        if (!file_exists($_ENV['PWD'] . '/phpunit.xml')) {
            $output .= self::formatWarningMessage($header . ': skipped - missing phpunit.xml') . PHP_EOL;
            return $output;
        }
        try {
            $output .= Utilities\System::getInstance()->run([
                $executable, $basePath . '/vendor/bin/phpunit', '--colors=always',
            ]);
        } catch (Utilities\Exceptions\Run $e) {
            throw new \Exception(
                self::formatErrorMessage($header . ' - analyse failed!') . PHP_EOL .
                $e->getOutput(),
            );
        }
        $output .= PHP_EOL;

        $output .= 'Done after ' . round(microtime(true) - $start, 3) . 's' . PHP_EOL;
        return $output;
    }

    private static function formatSuccessMessage(string $message) : string {
        return self::formatMessage($message,'black', 'green', []);
    }

    private static function formatWarningMessage(string $message) : string {
        return self::formatMessage($message,'black', 'yellow', []);
    }

    private static function formatErrorMessage(string $message) : string {
        return self::formatMessage($message, 'black', 'red', ['bold']);
    }

    private static function formatMessage(string $message, string $foreground, string $background, array $options) : string {

        $style = new Console\Formatter\OutputFormatterStyle($foreground, $background, $options);
        $formatter = new Console\Formatter\OutputFormatter(true);
        $formatter->setStyle('format', $style);

        return $formatter->format('<format>' . $formatter::escape($message) . '</format>');
    }
}
