<?php

namespace JKocik\Laravel\Profiler;

use JKocik\Laravel\Profiler\Contracts\Timer;
use JKocik\Laravel\Profiler\Contracts\DataTracker;
use JKocik\Laravel\Profiler\Contracts\DataProcessor;
use JKocik\Laravel\Profiler\Contracts\ExecutionData;
use JKocik\Laravel\Profiler\Contracts\ExecutionWatcher;
use JKocik\Laravel\Profiler\Services\Timer\TimerService;
use JKocik\Laravel\Profiler\Contracts\RequestHandledListener;
use JKocik\Laravel\Profiler\LaravelExecution\LaravelExecutionData;

class LaravelProfiler extends BaseProfiler
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(DataTracker::class, LaravelDataTracker::class);

        $this->app->bind(DataProcessor::class, LaravelDataProcessor::class);

        $this->app->bind(ExecutionWatcher::class, LaravelExecutionWatcher::class);

        $this->app->singleton(ExecutionData::class, function ($app) {
            return $app->make(LaravelExecutionData::class);
        });

        $this->app->singleton(Timer::class, function ($app) {
            return $app->make(TimerService::class);
        });

        $this->app->make(Timer::class)->startLaravel();
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $dataTracker = $this->app->make(DataTracker::class);
        $dataProcessor = $this->app->make(DataProcessor::class);
        $executionWatcher = $this->app->make(ExecutionWatcher::class);

        $executionWatcher->watch();
        $dataTracker->track();

        $this->app->terminating(function () use ($dataTracker, $dataProcessor) {
            $this->app->make(Timer::class)->finishLaravel();
            $dataTracker->terminate();
            $dataProcessor->process($dataTracker);
        });
    }
}
