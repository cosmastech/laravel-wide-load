<p align="center">
  <img src="assets/logo.png" alt="Wide Load for Laravel" width="256">
</p>

# Wide Load for Laravel

Wide event logging for Laravel — one log line for an entire request, packed with everything that happened.

Instead of scattering dozens of log lines throughout your request lifecycle, Wide Load collects key-value
data as your application runs and emits a single, rich log entry when the request, artisan command, 
or job completes. This is the "wide event" or "canonical log line" pattern. For more details on the benefits
of this approach, see [loggingsucks.com](https://loggingsucks.com).

## Installation

```bash
composer require cosmastech/laravel-wide-load
```

The package auto-discovers its service provider — no manual registration needed.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=wide-load-config
```

This creates `config/wide-load.php` with the following options:

| Option | Env Var | Default | Description |
|---|---|---|---|
| `enabled` | `WIDE_LOAD_ENABLED` | `true` | Master toggle for automatic reporting. When `false`, event-driven reporting is skipped. Manual calls to `report()` still work. |
| `log_level` | `WIDE_LOAD_LOG_LEVEL` | `info` | Log level used by the default reporter. |
| `log_message` | `WIDE_LOAD_LOG_MESSAGE` | `Request completed.` | Log message used by the default reporter. |
| `serializable` | `WIDE_LOAD_SERIALIZABLE` | `true` | Carry wide load data across queued jobs via Laravel's Context serialization. |

## Usage

### Via the Context macro

The quickest way to add data from anywhere in your app:

```php
use Illuminate\Support\Facades\Context;

Context::addWide('user_id', $user->id);
Context::addWide(['plan' => 'pro', 'locale' => 'en']);
```

### Via the WideLoad instance

Inject or resolve the `WideLoad` class directly for the full API:

```php
use Cosmastech\WideLoad\WideLoad;

$wideLoad = resolve(WideLoad::class);

// Add data
$wideLoad->add('user_id', $user->id);
$wideLoad->add(['plan' => 'pro', 'locale' => 'en']);

// Add only if the key doesn't exist yet
$wideLoad->addIf('request_id', Str::uuid());

// Increment a counter
$wideLoad->increment('db_queries');
$wideLoad->decrement('remaining_credits');
```

### Automatic reporting

Wide Load automatically calls `report()` and `flush()` on:

- `Terminating` (end of HTTP request)
- `CommandFinished` (end of Artisan command)
- `JobProcessed` (successful queue job)
- `JobFailed` (failed queue job)

No manual reporting is needed in most cases.

### Middleware

For more control over HTTP request reporting, you can register the terminable middleware:

```php
// bootstrap/app.php
use Cosmastech\WideLoad\WideLoadMiddleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->append(WideLoadMiddleware::class);
})
```

When the middleware is registered, it will report and flush during the `terminate` phase. The `Terminating` event listener will automatically skip reporting if the middleware has already handled it, so there is no double-reporting.

### Custom reporter

By default, Wide Load writes to the Laravel log. To send data somewhere else (a metrics service, a dedicated wide event store, etc.), register a custom callback in your `AppServiceProvider`:

```php
use Cosmastech\WideLoad\WideLoad;
use Illuminate\Support\Facades\Log;

public function boot(): void
{
    resolve(WideLoad::class)->reportUsing(static function (array $data): void {
        if (empty($data)) {
            return;
        }

        Log::info("[Shutdown] Request details", $data);
    });
}
```

## Events

Wide Load dispatches events during the `report()` call that you can listen to:

| Event | Description |
|---|---|
| `WideLoadReporting` | Dispatched when `report()` is called with data. The event contains the `array $data` being reported. |
| `NoWideLoadToReport` | Dispatched when `report()` is called but there is no data to report. |

```php
use Cosmastech\WideLoad\Events\WideLoadReporting;
use Cosmastech\WideLoad\Events\NoWideLoadToReport;
use Illuminate\Support\Facades\Event;

Event::listen(WideLoadReporting::class, function (WideLoadReporting $event) {
    // $event->data contains the reported key-value pairs
});

Event::listen(NoWideLoadToReport::class, function () {
    // No wide load data was collected during this lifecycle
});
```

## License

MIT
