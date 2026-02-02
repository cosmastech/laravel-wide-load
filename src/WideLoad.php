<?php

namespace Cosmastech\WideLoad;

use Closure;
use Cosmastech\WideLoad\Events\NoWideLoadToReport;
use Cosmastech\WideLoad\Events\WideLoadReported;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

#[Scoped]
class WideLoad
{
    public const string CONTEXT_KEY = '__wide_load';

    /** @var array<string, mixed> */
    protected array $data = [];

    /**
     * @var (Closure(array<string, mixed>): void)|null
     */
    protected ?Closure $reportCallback = null;

    public function __construct(
        protected readonly WideLoadConfig $config,
    ) {
    }

    /**
     * @param  string|array<string, mixed>  $key
     */
    public function add(string|array $key, mixed $value = null): static
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->data[$k] = $v;
            }
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function addIf(string $key, mixed $value): static
    {
        if (! $this->has($key)) {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);

        $this->forget($key);

        return $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }

    /**
     * @param  string|array<int, string>  $key
     */
    public function forget(string|array $key): static
    {
        $keys = is_array($key) ? $key : [$key];

        foreach ($keys as $k) {
            unset($this->data[$k]);
        }

        return $this;
    }

    public function flush(): static
    {
        $this->data = [];

        return $this;
    }

    public function increment(string $key, int $amount = 1): static
    {
        $this->data[$key] = ((int) $this->get($key, 0)) + $amount; // @phpstan-ignore cast.int (This is fine)

        return $this;
    }

    public function decrement(string $key, int $amount = 1): static
    {
        return $this->increment($key, -$amount);
    }

    /**
     * @return $this
     */
    public function report(): static
    {
        $data = $this->all();

        if ($data === []) {
            Event::dispatch(new NoWideLoadToReport());

            return $this;
        }

        Event::dispatch(new WideLoadReported($data));

        if ($this->reportCallback !== null) {
            call_user_func($this->reportCallback, $data);

            return $this;
        }

        Log::log($this->config->logLevel, $this->config->logMessage, $data);

        return $this;
    }

    /**
     * @param  (Closure(array<string, mixed>): void)|null  $callback
     * @return $this
     */
    public function reportUsing(?Closure $callback): static
    {
        $this->reportCallback = $callback;

        return $this;
    }
}
