<?php

declare(strict_types=1);

namespace Odaf\Runtime;

use Closure;
use RuntimeException;

/**
 * Service Registry (Vol.3 Bab 09).
 *
 * Resolusi service platform (dataset, security, validation, render, audit) yang
 * dibutuhkan runtime kernel. Membungkus container agar kernel tidak bergantung
 * langsung pada framework.
 *
 * @template TService of object
 */
final class ServiceRegistry
{
    /** @var array<string, Closure(): object> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $resolved = [];

    /**
     * Daftarkan factory untuk sebuah kontrak service.
     *
     * @param  class-string  $contract
     * @param  Closure(): object  $factory
     */
    public function register(string $contract, Closure $factory): void
    {
        $this->factories[$contract] = $factory;
    }

    /**
     * Apakah kontrak terdaftar.
     *
     * @param  class-string  $contract
     */
    public function has(string $contract): bool
    {
        return isset($this->factories[$contract]) || isset($this->resolved[$contract]);
    }

    /**
     * Resolusi service (singleton per registry).
     *
     * @template T of object
     *
     * @param  class-string<T>  $contract
     * @return T
     */
    public function resolve(string $contract): object
    {
        if (isset($this->resolved[$contract])) {
            /** @var T */
            return $this->resolved[$contract];
        }

        if (! isset($this->factories[$contract])) {
            throw new RuntimeException("Service tidak terdaftar di registry: {$contract}");
        }

        $instance = ($this->factories[$contract])();
        $this->resolved[$contract] = $instance;

        /** @var T */
        return $instance;
    }
}
