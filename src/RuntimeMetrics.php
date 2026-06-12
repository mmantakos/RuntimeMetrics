<?php

namespace mmantakos\RuntimeMetrics;

/**
 * RuntimeMetrics
 *
 * A lightweight utility for tracking execution time and memory usage in PHP.
 * Supports multiple named timers, lap checkpoints, formatted reporting,
 * and JSON export for profiling and performance analysis.
 *
 * Example:
 * $metrics = new RuntimeMetrics();
 * $metrics->measure(fn() => usleep(200_000), 'api');
 * echo $metrics->report('api');
 */
class RuntimeMetrics
{
    private array $timers = [];

    /**
     * Start a timer by name.
     */
    public function start(string $name = 'default'): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'end' => null,
            'startMemory' => memory_get_usage(true),
            'endMemory' => null,
            'startPeak' => memory_get_peak_usage(true),
            'endPeak' => null,
            'lastLap' => null,
        ];
    }

    /**
     * Stop a timer by name.
     */
    public function stop(string $name = 'default'): void
    {
        if (!isset($this->timers[$name]['start'])) {
            throw new \LogicException("Timer '$name' has not been started.");
        }

        $this->timers[$name]['end'] = microtime(true);
        $this->timers[$name]['endMemory'] = memory_get_usage(true);
        $this->timers[$name]['endPeak'] = memory_get_peak_usage(true);
    }

    /**
     * Record a lap (duration since last lap or start).
     */
    public function lap(string $name = 'default'): float
    {
        if (!isset($this->timers[$name]['start'])) {
            throw new \LogicException("Timer '$name' has not been started.");
        }

        $now = microtime(true);
        $lastLap = $this->timers[$name]['lastLap'] ?? $this->timers[$name]['start'];
        $this->timers[$name]['lastLap'] = $now;

        return $now - $lastLap;
    }

    /**
     * Get total duration (in seconds).
     */
    public function getDuration(string $name = 'default'): float
    {
        if (!isset($this->timers[$name]['start'])) {
            throw new \LogicException("Timer '$name' has not been started.");
        }

        $end = $this->timers[$name]['end'] ?? microtime(true);
        return $end - $this->timers[$name]['start'];
    }

    /**
     * Memory delta between start and end (formatted by default).
     */
    public function getMemoryUsage(string $name = 'default', bool $formatted = true): string|int
    {
        if (!isset($this->timers[$name]['startMemory'])) {
            throw new \LogicException("Timer '$name' has not been started.");
        }

        $endMem = $this->timers[$name]['endMemory'] ?? memory_get_usage(true);
        $startMem = $this->timers[$name]['startMemory'];
        $bytes = max(0, $endMem - $startMem);

        return $formatted ? $this->formatBytes($bytes) : $bytes;
    }

    /**
     * Peak memory usage during the run (formatted by default).
     */
    public function getPeakMemoryUsage(string $name = 'default', bool $formatted = true): string|int
    {
        if (!isset($this->timers[$name]['startPeak'])) {
            throw new \LogicException("Timer '$name' has not been started.");
        }

        $bytes = isset($this->timers[$name]['endPeak'])
            ? (int) $this->timers[$name]['endPeak']
            : memory_get_peak_usage(true);

        return $formatted ? $this->formatBytes($bytes) : $bytes;
    }

    /**
     * Measure a callback and record time + memory.
     */
    public function measure(callable $callback, string $name = 'default'): float
    {
        $this->start($name);
        $callback();
        $this->stop($name);
        return $this->getDuration($name);
    }

    /**
     * Reset a specific timer or all timers.
     */
    public function reset(?string $name = null): void
    {
        if ($name === null) {
            $this->timers = [];
        } else {
            unset($this->timers[$name]);
        }
    }

    /**
     * Get list of all timer names.
     */
    public function getTimers(): array
    {
        return array_keys($this->timers);
    }

    /**
     * Format bytes into human-readable units.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);

        if ($bytes === 0) {
            return '0 B';
        }

        $power = floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        $value = $bytes / (1024 ** $power);
        return number_format($value, $precision) . ' ' . $units[$power];
    }

    /**
     * Format seconds into human-readable units.
     */
    public function formatSeconds(float $seconds, int $precision = 3): string
    {
        if ($seconds < 1) {
            return round($seconds * 1000, $precision) . ' ms';
        }
        return round($seconds, $precision) . ' s';
    }

    /**
     * Generate a human-readable report for a timer.
     */
    public function report(string $name = 'default', bool $formatted = true): string
    {
        if (!isset($this->timers[$name])) {
            throw new \LogicException("Timer '$name' not found.");
        }

        $duration = $this->getDuration($name);
        $mem = $this->getMemoryUsage($name, !$formatted ? false : true);
        $peak = $this->getPeakMemoryUsage($name, !$formatted ? false : true);

        if ($formatted) {
            $duration = $this->formatSeconds($duration);
        }

        return sprintf("[%s] %s, used %s (peak %s)", $name, $duration, $mem, $peak);
    }

    /**
     * Generate reports for all timers.
     */
    public function reportAll(bool $formatted = true): string
    {
        $lines = [];
        foreach ($this->timers as $name => $_) {
            $lines[] = $this->report($name, $formatted);
        }
        return implode(PHP_EOL, $lines);
    }

    /**
     * Return all timers as an array for structured output.
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->timers as $name => $_) {
            $result[$name] = [
                'duration' => $this->getDuration($name),
                'memory' => $this->getMemoryUsage($name, false),
                'peak' => $this->getPeakMemoryUsage($name, false),
            ];
        }
        return $result;
    }

    /**
     * Return all timers as a JSON string.
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->toArray(), $flags);
    }
}
