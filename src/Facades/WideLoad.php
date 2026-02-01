<?php

namespace Cosmastech\WideLoad\Facades;

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
 * @method static \Cosmastech\WideLoad\WideLoad push(string $key, mixed ...$values)
 * @method static \Cosmastech\WideLoad\WideLoad increment(string $key, int $amount = 1)
 * @method static \Cosmastech\WideLoad\WideLoad decrement(string $key, int $amount = 1)
 * @method static void report()
 * @method static \Cosmastech\WideLoad\WideLoad reportUsing(callable $callback)
 * @method static bool enabled()
 * @method static \Cosmastech\WideLoad\WideLoad enable()
 * @method static \Cosmastech\WideLoad\WideLoad disable()
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
