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

namespace BettIngenieure\Utilities;

use BettIngenieure\Base\BaseClass;
use BettIngenieure\Base\SingletonTrait;
use Symfony;

class System extends BaseClass { 

    use SingletonTrait;

    /**
     * @deprecated
     *
     * @return array<string>
     * @throws Exceptions\Exec
     */
    public function exec(string $cmd, int $expectedReturnVar = null) : array {

        if($expectedReturnVar === null) {
            $expectedReturnVar = 0;
        }

        exec($cmd, $output, $return_var);

        if($return_var !== $expectedReturnVar) {

            $message = 'Unexpected return var ' . var_export($return_var, true) . ' while executing: ' . PHP_EOL .
                $cmd . PHP_EOL .
                'Output: ' . PHP_EOL .
                var_export($output, true)
            ;

            throw new Exceptions\Exec($message, $output, $cmd);
        }

        /** @var string[] $output */
        return $output;
    }

    /**
     * @throws Exceptions\Run
     */
    public function run(array $command, int $expectedReturnVar = null, string $cwd = null, array $environment = null, int $timeout = null, bool $trimOutput = true) : string {

        if($expectedReturnVar === null) {
            $expectedReturnVar = 0;
        }

        $process = new Symfony\Component\Process\Process($command, $cwd, $environment, null, $timeout ?? 30*60);
        try {
            $process->run();
        } catch (\Exception $e) {

            $message = 'Unexpected exception thrown while running process: ' . PHP_EOL .
                var_export($command, true) . PHP_EOL .
                'Message: ' . $e->getMessage() . PHP_EOL .
                'Output: ' . PHP_EOL .
                ($output = $process->getOutput())
            ;

            throw new Exceptions\Run($message,  $output, $command);
        }

        $output = $process->getOutput();

        if($process->getExitCode() !== $expectedReturnVar) {

            $message = 'Unexpected return var ' . var_export($process->getExitCode() . ': ' . $process->getExitCodeText(), true) . ' while executing: ' . PHP_EOL .
                var_export($command, true) . PHP_EOL .
                'Output: ' . PHP_EOL .
                $output
            ;

            throw new Exceptions\Run($message,  $output, $command);
        }

        return $trimOutput ? trim($output) : $output;
    }

    /**
     * @throws Exceptions\Run
     */
    public function runOnBashShell(array $command, int $expectedReturnVar = null, string $cwd = null, array $environment = null, int $timeout = null, bool $trimOutput = true) : string {
        return $this->runOnBashShellWithPlaceholder((new Symfony\Component\Process\Process($command))->getCommandLine(), [], $expectedReturnVar, $cwd, $environment, $timeout, $trimOutput);
    }

    /**
     * @throws Exceptions\Run
     */
    public function runOnBashShellWithPlaceholder(string $commandTemplate, array $replacements, int $expectedReturnVar = null, string $cwd = null, array $environment = null, int $timeout = null, bool $trimOutput = true) : string {

        $command = (new Interpolator())->do(
            $commandTemplate,
            $replacements,
            fn(string $s) => (new Symfony\Component\Process\Process([$s]))->getCommandLine(),
        );

        return $this->run(['bash', '-c', $command], $expectedReturnVar, $cwd, $environment, $timeout, $trimOutput);
    }
}