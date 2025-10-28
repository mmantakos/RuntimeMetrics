# RuntimeMetrics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mmantakos/runtime-metrics.svg?style=flat-square)](https://packagist.org/packages/mmantakos/runtime-metrics)
[![PHP Version](https://img.shields.io/packagist/php-v/mmantakos/runtime-metrics.svg?style=flat-square)](https://packagist.org/packages/mmantakos/runtime-metrics)
[![CI](https://github.com/mmantakos/RuntimeMetrics/workflows/CI/badge.svg)](https://github.com/mmantakos/RuntimeMetrics/actions)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![codecov](https://codecov.io/gh/mmantakos/RuntimeMetrics/branch/main/graph/badge.svg?token=RJMYG1E1P0)](https://codecov.io/gh/mmantakos/RuntimeMetrics)
---
## Synopsis
A lightweight PHP library for tracking **execution time** and **memory usage** of your code, with multiple named timers, lap support, human-readable reports, and JSON export. Perfect for benchmarking, profiling, and performance monitoring.

---

## Features

- Start, stop, and lap multiple timers
- Measure memory usage and peak memory
- Human-readable formatting (e.g., `2.45 MB`, `125 ms`)
- Generate reports for single or all timers
- Export metrics as structured arrays or JSON
- Easy to integrate in any PHP project
- Fully unit-tested

---

## Installation

Install via Composer:

```bash
composer require mmantakos/runtime-metrics
```
---

## Quick Start
```
<?php

use mmantakos\RuntimeMetrics\RuntimeMetrics;

$metrics = new RuntimeMetrics();

// Start a timer
$metrics->start('api');
usleep(200_000); // simulate work
$metrics->stop('api');

// Report single timer
echo $metrics->report('api'); 
// [api] 200.51 ms, used 8 KB (peak 2 MB)

// Measure using a callback
$metrics->measure(function () {
    usleep(100_000);
}, 'callback');
echo $metrics->report('callback');

// Lap timers
$metrics->start('loop');
usleep(50_000);
echo $metrics->lap('loop'); // ~50 ms
usleep(100_000);
echo $metrics->lap('loop'); // ~100 ms
$metrics->stop('loop');

// Memory intensive operation
$metrics->start('heavy');
$data = range(1, 1000000);
$metrics->stop('heavy');
echo $metrics->report('heavy'); 
// [heavy] 120.23 ms, used 15.3 MB (peak 20.4 MB)

// Report all timers
echo $metrics->reportAll();

// Export as JSON
echo $metrics->toJson(true);
/*
{
    "api": {
        "duration": "200.51 ms",
        "memory": "8 KB",
        "peak": "2 MB"
    },
    "callback": { ... },
    "loop": { ... },
    "heavy": { ... }
}
*/
```
---
## Methods

| Method | Description |
|--------|-------------|
| `start(string $name = 'default')` | Start a new timer |
| `stop(string $name = 'default')` | Stop a timer |
| `lap(string $name = 'default')` | Record a lap time |
| `getDuration(string $name = 'default')` | Get total duration in seconds |
| `getMemoryUsage(string $name = 'default', bool $formatted = true)` | Get memory delta |
| `getPeakMemoryUsage(string $name = 'default', bool $formatted = true)` | Get peak memory |
| `measure(callable $callback, string $name = 'default')` | Measure a callback |
| `report(string $name, bool $formatted = true)` | Human-readable report for one timer |
| `reportAll(bool $formatted = true)` | Human-readable report for all timers |
| `toArray(bool $formatted = false)` | Export timers as an array |
| `toJson(bool $formatted = false)` | Export timers as JSON |
| `reset(?string $name = null)` | Clear one or all timers |
| `formatBytes(int $bytes, int $precision = 2)` | Convert bytes to human-readable string |
| `formatSeconds(float $seconds, int $precision = 3)` | Convert seconds to human-readable string |
