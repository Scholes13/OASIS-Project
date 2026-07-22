<?php

namespace App\Services\Modules\Ticket\LegacyImport;

final class LegacyImportReport
{
    /** @var array<string, int> */
    private array $stats = [];

    /** @var list<string> */
    private array $errors = [];

    /** @var list<string> */
    private array $warnings = [];

    public function increment(string $metric, int $by = 1): void
    {
        $this->stats[$metric] = ($this->stats[$metric] ?? 0) + $by;
    }

    public function incrementWhen(string $metric, bool $condition): void
    {
        if ($condition) {
            $this->increment($metric);
        }
    }

    public function error(string $message): void
    {
        $this->errors[] = $message;
    }

    public function warning(string $message): void
    {
        $this->warnings[] = $message;
    }

    /** @return array<string, int> */
    public function stats(): array
    {
        return $this->stats;
    }

    /** @return list<string> */
    public function errors(): array
    {
        return array_values(array_unique($this->errors));
    }

    /** @return list<string> */
    public function warnings(): array
    {
        return array_values(array_unique($this->warnings));
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function mergeWarningsFrom(self $report): void
    {
        foreach ($report->warnings() as $warning) {
            $this->warning($warning);
        }
    }
}
