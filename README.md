# A job middleware to funnel jobs

[![Latest Version on Packagist](https://img.shields.io/packagist/v/telkins/laravel-job-funneling-middleware.svg?style=flat-square)](https://packagist.org/packages/telkins/laravel-job-funneling-middleware)
[![Build Status](https://img.shields.io/travis/telkins/laravel-job-funneling-middleware/master.svg?style=flat-square)](https://travis-ci.org/telkins/laravel-job-funneling-middleware)
[![Quality Score](https://img.shields.io/scrutinizer/g/telkins/laravel-job-funneling-middleware.svg?style=flat-square)](https://scrutinizer-ci.com/g/telkins/laravel-job-funneling-middleware)
[![StyleCI](https://github.styleci.io/repos/211561705/shield?branch=master)](https://github.styleci.io/repos/211561705)
[![Total Downloads](https://img.shields.io/packagist/dt/telkins/laravel-job-funneling-middleware.svg?style=flat-square)](https://packagist.org/packages/telkins/laravel-job-funneling-middleware)

This package contains a [job middleware](https://laravel.com/docs/master/queues#job-middleware) that can funnel jobs in Laravel apps.

## Special Credits

Permission was granted by [Freek Van der Herten](https://github.com/freekmurze) to copy Spatie's [laravel-rate-limited-job-middleware](https://github.com/spatie/laravel-rate-limited-job-middleware), rename it, and maintain it on my own. As such, the vast bulk of this package is built on theirs. Thanks...!  :-)

## Installation

You can install the package via composer:

```bash
composer require telkins/laravel-job-funneling-middleware
```

This package requires Redis to be set up in your Laravel app.

## Usage

By default, the middleware will only allow 1 job to be executed at a time. Any jobs that are not allowed will be released for 5 seconds. 

To apply the middleware just add the `Telkins\JobFunnelingMiddleware\Funneled` to the middlewares of your job.

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Telkins\JobFunnelingMiddleware\Funneled;

class TestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        // your job logic
    }

    public function middleware()
    {
        return [new Funneled()];
    }
}
```

### Configuring attempts

When using rate limiting, including funneling, the number of attempts of your job may be hard to predict. Instead of using a fixed number of attempts, it's better to use [time based attempts](https://laravel.com/docs/master/queues#time-based-attempts).

You can add this to your job class:

```php
/*
 * Determine the time at which the job should timeout.
 *
 */
public function retryUntil() :  \DateTime
{
    return now()->addDay();
}
```

### Customizing the behavior

You can customize all the behavior. Here's an example where the middleware allows a maximum of 3 jobs to be performed at a time. Jobs that are not allowed will be released for 90 seconds.

```php
// in your job

public function middleware()
{
    $funneledMiddleware = (new Funneled())
        ->limit(3)
        ->releaseAfterSeconds(90);

    return [$funneledMiddleware];
}
```

### Customizing Redis

By default, the middleware will use the default Redis connection. 

The default key that will be used in redis will be the name of the class that created the instance of the middleware. In most cases this will be name of the job in which the middleware is applied. If this is not what you expect, you can use the `key` method to customize it.

Here's an example where a custom connection and custom key is used.

```php
// in your job

public function middleware()
{
    $funneledMiddleware = (new Funneled())
        ->connection('my-custom-connection')
        ->key('my-custom-key');

    return [$funneledMiddleware];
}
```

### Conditionally applying the middleware

If you want to conditionally apply the middleware you can use the `enabled` method. It accepts a boolean that determines if the middleware should funnel your job or not.

You can also pass a `Closure` to `enabled`. If it evaluates to a truthy value the middleware will be enabled.

Here's a silly example where the funneling is only activated in January.

```php
// in your job

public function middleware()
{
    $shouldFunnelJobs = Carbon::now()->month === 1;

    $funneledMiddleware = (new Funneled())
        ->enabled($shouldFunnelJobs);

    return [$funneledMiddleware];
}
```

### Available methods.

These methods are available to be called on the middleware. Their names should be self-explanatory.

- `limit(int $limitedNumberOfJobs)`
- `releaseAfterOneSecond()`
- `releaseAfterSeconds(int $releaseInSeconds)`
- `releaseAfterOneMinute()`
- `releaseAfterMinutes(int $releaseInMinutes)`
- `releaseAfterRandomSeconds(int $min = 1, int $max = 10)`

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email travis.elkins+github@gmail.com instead of using the issue tracker.

## Credits

- [Travis Elkins](https://github.com/telkins)
- [All Contributors](../../contributors)

This code is heavily based on [the funneling example](https://laravel.com/docs/master/queues#job-middleware) found in the Laravel docs.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
