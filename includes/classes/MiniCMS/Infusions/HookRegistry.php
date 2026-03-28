<?php

namespace App\MiniCMS\Infusions;

final class HookRegistry
{
    private array $listeners = [];

    public function add(string $hook, callable $listener, int $priority = 10): void
    {
        $hook = trim($hook);
        if ($hook === '') {
            return;
        }

        $this->listeners[$hook][$priority][] = $listener;
        ksort($this->listeners[$hook], SORT_NUMERIC);
    }

    public function has(string $hook): bool
    {
        return !empty($this->listeners[$hook]);
    }

    public function dispatch(string $hook, $payload = null, array $context = [])
    {
        if (!$this->has($hook)) {
            return $payload;
        }

        foreach ($this->listeners[$hook] as $listenersByPriority) {
            foreach ($listenersByPriority as $listener) {
                $result = $listener($payload, $context);
                if ($result !== null) {
                    $payload = $result;
                }
            }
        }

        return $payload;
    }

    public function filter(string $hook, $value, array $context = [])
    {
        return $this->dispatch($hook, $value, $context);
    }

    public function all(): array
    {
        return $this->listeners;
    }
}
