<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Database\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log detailed error information with request context
            Log::error('Application error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'headers' => request()->headers->all(),
                'input' => request()->except(['password', 'password_confirmation']),
                'user_id' => auth()->id(),
                'ip' => request()->ip()
            ]);
        });

        $this->renderable(function (Throwable $e) {
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->validator->getMessageBag()
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'message' => 'This action is unauthorized.'
                ], 403);
            }

            if ($e instanceof QueryException) {
                Log::error('Database error: ' . $e->getMessage());
                return response()->json([
                    'message' => 'A database error occurred. Please try again later.',
                    'error_code' => 'DB_ERROR'
                ], 500);
            }

            if ($e instanceof ConnectionException) {
                Log::error('Connection error: ' . $e->getMessage());
                return response()->json([
                    'message' => 'A connection error occurred. Please try again later.',
                    'error_code' => 'CONNECTION_ERROR'
                ], 503);
            }

            // Handle all other exceptions
            if (config('app.debug')) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'error_code' => 'INTERNAL_ERROR',
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->map(function ($trace) {
                        return collect($trace)->except(['args'])->all();
                    })->all()
                ], 500);
            }

            // Log the error with a unique identifier
            $errorId = uniqid('err_');
            Log::error("Error ID: {$errorId}", ['exception' => $e]);

            return response()->json([
                'message' => 'An unexpected error occurred. Please try again later.',
                'error_code' => 'INTERNAL_ERROR',
                'error_id' => $errorId
            ], 500);
        });
    }
}
