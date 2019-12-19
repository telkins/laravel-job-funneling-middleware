<?php

namespace Telkins\JobFunnelingMiddleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class Funneled
{
    /** @var bool|\Closure */
    protected $enabled = true;

    /** @var string */
    protected $connectionName = '';

    /** @var string */
    protected $key;

    /** @var int */
    protected $allowedNumberOfJobs = 1;

    /** @var int */
    protected $releaseInSeconds = 5;

    /** @var array */
    protected $releaseRandomSeconds = null;

    public function __construct()
    {
        $calledByClass = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'];

        $this->key($calledByClass);
    }

    /**
     * @param bool|\Closure $enabled
     *
     * @return $this
     */
    public function enabled($enabled = true)
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function connectionName(string $connectionName)
    {
        $this->connectionName = $connectionName;

        return $this;
    }

    public function key(string $key)
    {
        $this->key = $key;

        return $this;
    }

    public function allow(int $allowedNumberOfJobs)
    {
        $this->allowedNumberOfJobs = $allowedNumberOfJobs;

        return $this;
    }

    public function releaseAfterOneSecond()
    {
        return $this->releaseAfterSeconds(1);
    }

    public function releaseAfterSeconds(int $releaseInSeconds)
    {
        $this->releaseInSeconds = $releaseInSeconds;

        return $this;
    }

    public function releaseAfterOneMinute()
    {
        return $this->releaseAfterMinutes(1);
    }

    public function releaseAfterMinutes(int $releaseInMinutes)
    {
        return $this->releaseAfterSeconds($releaseInMinutes * 60);
    }

    public function releaseAfterRandomSeconds(int $min = 1, int $max = 10)
    {
        $this->releaseRandomSeconds = [$min, $max];

        return $this;
    }

    protected function releaseDuration() :int
    {
        if (! is_null($this->releaseRandomSeconds)) {
            return random_int(...$this->releaseRandomSeconds);
        }

        return $this->releaseInSeconds;
    }

    public function handle($job, $next)
    {
        if ($this->enabled instanceof Closure) {
            $this->enabled = (bool) $this->enabled();
        }

        if (! $this->enabled) {
            return $next($job);
        }

        Redis::connection($this->connectionName)
            ->funnel($this->key)
            ->limit($this->allowedNumberOfJobs)
            ->then(function () use ($job, $next) {
                $next($job);
            }, function () use ($job) {
                $job->release($this->releaseDuration());
            });
    }
}
