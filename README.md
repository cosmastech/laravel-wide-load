# Laravel Wide Load

Wide event logging for Laravel — one log line per request, packed with everything that happened.

Instead of scattering dozens of log lines throughout your request lifecycle, Wide Load collects key-value data as your application runs and emits a single, rich log entry when the request (or job) completes. This is the "wide event" or "canonical log line" pattern. For more on why this approach is better, see [loggingsucks.com](https://loggingsucks.com).

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
| `enabled` | `WIDE_LOAD_ENABLED` | `true` | Master toggle. When `false`, `report()` is a no-op. |
| `log_level` | `WIDE_LOAD_LOG_LEVEL` | `info` | Log level used by the default reporter. |
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

// Append to an array
$wideLoad->push('tags', 'slow-query');

// Increment a counter
$wideLoad->increment('db_queries');
$wideLoad->decrement('remaining_credits');
```

### Automatic reporting

Wide Load automatically calls `report()` and `flush()` on:

- `Terminating` (end of HTTP request)
- `JobProcessed` (successful queue job)
- `JobFailed` (failed queue job)

No manual reporting is needed in most cases.

### Custom reporter

By default, Wide Load writes to the Laravel log. To send data somewhere else (a metrics service, a dedicated wide event store, etc.), register a custom callback in your `AppServiceProvider`:

```php
use Cosmastech\WideLoad\WideLoad;

public function boot(): void
{
    app(WideLoad::class)->reportUsing(function (array $data): void {
        if (empty($data)) {
            return;
        }

        \Log::info("[Shutdown] Request details", $data);
    });
}
```

Pass `null` to `reportUsing()` to revert to the default log behavior.

## License

MIT
