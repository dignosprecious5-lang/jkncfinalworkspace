<?php

declare(strict_types=1);

namespace Tests\Support {
    if (PHP_SAPI !== 'cli' || ! defined('TRANSMITTAL_OCR_MOCKED_LAUNCHER')
        || TRANSMITTAL_OCR_MOCKED_LAUNCHER !== true) {
        throw new \RuntimeException('The OCR Process double cannot load outside its dedicated mocked launcher.');
    }
    TransmittalOcrIsolation::assertEnvironment();

    // Conditional declarations prevent this file from defining a double on a rejected load.
    if (defined('TRANSMITTAL_OCR_MOCKED_LAUNCHER') && TRANSMITTAL_OCR_MOCKED_LAUNCHER === true) {
        class TransmittalOcrProcessDouble
        {
            private static array $steps = [];
            private static array $calls = [];
            private static array $violations = [];
            private static int $clock = 1_000_000_000;
            private array $arguments;
            private ?float $timeout = null;
            private bool $ran = false;
            private bool $success = false;
            private string $stdout = '';

            public static function reset(array $steps = []): void
            {
                TransmittalOcrIsolation::assertDouble();
                foreach ($steps as $step) {
                    if (! is_array($step) || ! in_array($step['kind'] ?? null, ['ocr', 'pdf'], true)
                        || array_diff(array_keys($step), ['kind', 'stdout', 'pages', 'success', 'throw', 'advance']) !== []) {
                        throw new \RuntimeException('Invalid synthetic process plan.');
                    }
                }
                self::$steps = array_values($steps);
                self::$calls = self::$violations = [];
                self::$clock = 1_000_000_000;
            }

            public static function calls(): array { return self::$calls; }
            public static function pending(): array { return self::$steps; }
            public static function violations(): array { return self::$violations; }
            public static function clock(): int { return self::$clock; }

            private static function refuse(string $message): never
            {
                // The service catches Throwable; retain violations so teardown cannot hide them.
                self::$violations[] = $message;
                throw new \RuntimeException($message);
            }

            public function __construct(array $command, ...$options)
            {
                TransmittalOcrIsolation::assertDouble();
                $fromService = false;
                foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
                    if (($frame['class'] ?? null) === \App\Services\TransmittalOcrService::class
                        && ($frame['function'] ?? null) === 'runProcess') {
                        $fromService = true;
                    }
                }
                if (! $fromService || $options !== [] || ! array_is_list($command)
                    || ($command[0] ?? null) !== PHP_BINARY) {
                    self::refuse('Unexpected consumer, executable, or Process constructor options.');
                }
                foreach ($command as $argument) {
                    if (! is_string($argument) || str_contains($argument, "\0")) {
                        self::refuse('Only synthetic string argument arrays are supported.');
                    }
                }
                $this->arguments = $command;
            }

            public function setTimeout(?float $timeout): static
            {
                if ($timeout === null || ! is_finite($timeout) || $timeout <= 0 || $timeout > 25) {
                    self::refuse('The bounded OCR process timeout was not retained.');
                }
                $this->timeout = $timeout;
                return $this;
            }

            public function run(): int
            {
                if ($this->ran || $this->timeout === null || self::$steps === []) {
                    self::refuse('Unplanned, repeated, or unbounded process execution.');
                }
                $this->ran = true;
                $arguments = $this->arguments;
                $kind = count($arguments) === 3 && $arguments[2] === 'stdout' ? 'ocr' : 'pdf';
                $step = array_shift(self::$steps);
                if ($step['kind'] !== $kind) self::refuse('Unexpected OCR process sequence.');
                $runtime = str_replace('\\', '/', TransmittalOcrIsolation::runtime()).'/';
                $input = $kind === 'ocr' ? $arguments[1] : ($arguments[8] ?? '');
                $canonical = realpath($input);
                if ($canonical === false || is_link($input)
                    || ! str_starts_with(str_replace('\\', '/', $canonical), $runtime)) {
                    self::refuse('A process input escaped synthetic temporary storage.');
                }
                if ($kind === 'pdf') {
                    if (count($arguments) !== 10 || array_slice($arguments, 1, 7)
                        !== ['-f', '1', '-l', '5', '-scale-to', '1800', '-png']) {
                        self::refuse('PDF page or rendering bounds changed.');
                    }
                    $prefix = $arguments[9];
                    $directory = realpath(dirname($prefix));
                    if ($directory === false || is_link(dirname($prefix))
                        || ! str_starts_with(str_replace('\\', '/', $directory).'/', $runtime.'tmp/')
                        || basename($prefix) !== 'page') {
                        self::refuse('Unsafe PDF temporary output prefix.');
                    }
                    $pages = $step['pages'] ?? 1;
                    if (! is_int($pages) || $pages < 0 || $pages > 6) self::refuse('Invalid synthetic page count.');
                    for ($page = 1; $page <= $pages; $page++) {
                        if (file_put_contents($prefix.'-'.$page.'.png', TransmittalOcrFixtures::pngBytes()) === false) {
                            self::refuse('Synthetic page creation failed.');
                        }
                    }
                }
                self::$calls[] = ['kind' => $kind, 'arguments' => $arguments, 'timeout' => $this->timeout];
                $advance = $step['advance'] ?? 0;
                if (! is_int($advance) || $advance < 0 || $advance > 100) self::refuse('Invalid synthetic clock advance.');
                self::$clock += $advance * 1_000_000_000;
                if (isset($step['throw'])) {
                    if (! in_array($step['throw'], ['timeout', 'failure'], true)) self::refuse('Unsupported synthetic exception.');
                    throw new \RuntimeException('Synthetic OCR '.$step['throw'].'.');
                }
                $this->success = $step['success'] ?? true;
                $this->stdout = $step['stdout'] ?? '';
                return $this->success ? 0 : 1;
            }

            public function isSuccessful(): bool
            {
                if (! $this->ran) self::refuse('Process status requested before the synthetic run.');
                return $this->success;
            }

            public function getOutput(): string
            {
                if (! $this->ran) self::refuse('Output requested before the synthetic run.');
                return $this->stdout;
            }

            public function __call(string $name, array $arguments): never { self::refuse('Unsupported Process method: '.$name); }
            public static function __callStatic(string $name, array $arguments): never { self::refuse('Unsupported Process static method: '.$name); }
        }
    }
}

namespace Symfony\Component\Process {
    if (defined('TRANSMITTAL_OCR_MOCKED_LAUNCHER') && TRANSMITTAL_OCR_MOCKED_LAUNCHER === true
        && PHP_SAPI === 'cli') {
        final class Process extends \Tests\Support\TransmittalOcrProcessDouble {}
    }
}

namespace App\Services {
    if (defined('TRANSMITTAL_OCR_MOCKED_LAUNCHER') && TRANSMITTAL_OCR_MOCKED_LAUNCHER === true
        && PHP_SAPI === 'cli') {
        // Test-only namespace shims: production service source remains unchanged.
        function hrtime(bool $asNumber = false): array|int
        {
            $now = \Tests\Support\TransmittalOcrProcessDouble::clock();
            return $asNumber ? $now : [intdiv($now, 1_000_000_000), $now % 1_000_000_000];
        }
        function sys_get_temp_dir(): string
        {
            return \Tests\Support\TransmittalOcrIsolation::runtime().'/tmp';
        }
    }
}
