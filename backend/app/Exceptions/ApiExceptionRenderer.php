<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class ApiExceptionRenderer
{
    private const MODEL_NAMES = [
        'Team'        => 'El equipo',
        'Player'      => 'El jugador',
        'Competition' => 'La competición',
        'User'        => 'El usuario',
        'Role'        => 'El rol',
    ];

    // Formato único: { message, code, errors?, retry_after? }
    public static function render(Throwable $e): JsonResponse
    {
        [$status, $code, $message, $extra] = match (true) {
            $e instanceof ValidationException => [422, 'VALIDATION_ERROR', 'Los datos enviados no son válidos.', ['errors' => $e->errors()]],
            $e instanceof AuthenticationException => [401, 'UNAUTHENTICATED', 'Tu sesión no es válida o expiró. Inicia sesión nuevamente.', []],
            $e instanceof FootballDataException => [$e->status, $e->errorCode, $e->getMessage(), $e->retryAfter ? ['retry_after' => $e->retryAfter] : []],
            $e instanceof AccessDeniedHttpException => [403, 'FORBIDDEN', self::forbiddenMessage($e), []],
            $e instanceof NotFoundHttpException => [404, 'NOT_FOUND', self::notFoundMessage($e), []],
            $e instanceof TooManyRequestsHttpException => [429, 'TOO_MANY_REQUESTS', 'Demasiadas solicitudes. Espera un momento.', ['retry_after' => (int) ($e->getHeaders()['Retry-After'] ?? 60)]],
            $e instanceof HttpExceptionInterface => [$e->getStatusCode(), 'HTTP_ERROR', $e->getMessage() ? : 'Error en la solicitud.', []],
            default => [500, 'SERVER_ERROR', config('app.debug') ? $e->getMessage() : 'Error interno del servidor.', []],
        };

        $headers = isset($extra['retry_after']) ? ['Retry-After' => $extra['retry_after']] : [];

        return response()->json(['message' => $message, 'code' => $code, ...$extra], $status, $headers);
    }

    private static function notFoundMessage(NotFoundHttpException $e): string
    {
        $previous = $e->getPrevious();

        if ($previous instanceof ModelNotFoundException) {
            $name = self::MODEL_NAMES[class_basename($previous->getModel())] ?? 'El recurso';

            return "{$name} solicitado no existe.";
        }

        return 'El recurso solicitado no existe.';
    }

    private static function forbiddenMessage(AccessDeniedHttpException $e): string
    {
        $message = $e->getMessage();

        return $message && $message !== 'This action is unauthorized.'
            ? $message
            : 'No tienes permiso para realizar esta acción.';
    }
}
