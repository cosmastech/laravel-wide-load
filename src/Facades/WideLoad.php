<?php

namespace Cosmastech\WideLoad\Facades;

use Closure;
use Cosmastech\WideLoad\WideLoad as ClassWideLoad;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Cosmastech\WideLoad\WideLoad add(string|array $key, mixed $value = null)
 * @method static \Cosmastech\WideLoad\WideLoad addIf(string $key, mixed $value)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed pull(string $key, mixed $default = null)
 * @method static bool has(string $key)
 * @method static array all()
 * @method static array only(array $keys)
 * @method static array except(array $keys)
 * @method static \Cosmastech\WideLoad\WideLoad forget(string|array $key)
 * @method static \Cosmastech\WideLoad\WideLoad flush()
 * @method static \Cosmastech\WideLoad\WideLoad increment(string $key, int $amount = 1)
 * @method static \Cosmastech\WideLoad\WideLoad decrement(string $key, int $amount = 1)
 * @method static \Cosmastech\WideLoad\WideLoad report()
 * @method static \Cosmastech\WideLoad\WideLoad reportUsing(?Closure $callback)
 * @method static \Cosmastech\WideLoad\WideLoad enableAutoReporting(bool $enabled = true)
 * @method static bool isAutoReportingEnabled()
 *
 * @see ClassWideLoad
 */
class WideLoad extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ClassWideLoad::class;
    }
}
