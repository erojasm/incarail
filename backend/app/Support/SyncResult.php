<?php

namespace App\Support;

// Resultado de una lectura: de dónde salió el dato y si hubo algún aviso
final readonly class SyncResult
{
    public function __construct(
        public mixed $data,
        public string $source,
        public ?string $warning = null,
    ) {}

    public function meta(): array
    {
        return ['source' => $this->source, 'warning' => $this->warning];
    }
}
