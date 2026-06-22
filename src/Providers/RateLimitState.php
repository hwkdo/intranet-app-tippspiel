<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Providers;

/**
 * Hält den aktuellen Rate-Limit-Zustand des football-data.org API-Clients.
 * In-Process – nicht persistent. Pro Artisan-Command-Lauf neu initialisiert.
 */
class RateLimitState
{
    private int $requestsAvailable = 10;

    private int $resetInSeconds = 60;

    private float $lastResponseAt = 0.0;

    public function update(int $requestsAvailable, int $resetInSeconds): void
    {
        $this->requestsAvailable = $requestsAvailable;
        $this->resetInSeconds = $resetInSeconds;
        $this->lastResponseAt = microtime(true);
    }

    /**
     * Blockiert solange, bis sicher ein weiterer Request abgesetzt werden kann.
     */
    public function throttleIfNeeded(): void
    {
        if ($this->requestsAvailable <= 1 && $this->lastResponseAt > 0) {
            $elapsed = microtime(true) - $this->lastResponseAt;
            $waitSeconds = max(0, $this->resetInSeconds + 1 - $elapsed);

            if ($waitSeconds > 0) {
                sleep((int) ceil($waitSeconds));
            }

            // Reset-Annahme: nach dem Warten hat der Zähler sich erholt
            $this->requestsAvailable = 10;
        }
    }

    public function getRequestsAvailable(): int
    {
        return $this->requestsAvailable;
    }

    public function getResetInSeconds(): int
    {
        return $this->resetInSeconds;
    }
}
