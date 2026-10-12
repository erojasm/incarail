<?php

namespace App\Exceptions;

use RuntimeException;

class FootballDataException extends RuntimeException
{
    public function __construct(
        string                 $message,
        public readonly int    $status,
        public readonly string $errorCode,
        public readonly ?int   $retryAfter = null,
    )
    {
        parent::__construct($message);
    }

    public static function rateLimited(int $seconds): self
    {
        $seconds = max($seconds, 1);

        return new self(
            "Se alcanzó el límite de peticiones a football-data. Intenta de nuevo en {$seconds} segundos.",
            429,
            'UPSTREAM_RATE_LIMIT',
            $seconds,
        );
    }

    public static function unavailable(string $message = 'No se pudo conectar con football-data. Intenta más tarde.'): self
    {
        return new self($message, 503, 'UPSTREAM_UNAVAILABLE');
    }

    public static function badResponse(int $status): self
    {
        return new self("football-data respondió con un error ({$status}).", 502, 'UPSTREAM_ERROR');
    }

    public static function restricted(): self
    {
        return new self('Esta competición no está disponible en el plan gratuito de football-data.', 403, 'UPSTREAM_RESTRICTED');
    }

    public static function notFound(): self
    {
        return new self('La competición no existe en football-data.', 404, 'UPSTREAM_NOT_FOUND');
    }
}
