<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\EmployeeSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');
        $middleware->statefulApi();
        $middleware->api(append: ['auth:sanctum', EmployeeSession::class]);
        $middleware->web(append: [
            HandleInertiaRequests::class,
            EmployeeSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (
            Response $response,
            Throwable $exception,
            Request $request
        ): Response {
            if (
                $response->getStatusCode() === 419
                && ! $request->is('api/*')
                && ($request->header('X-Inertia') || ! $request->expectsJson())
            ) {
                Auth::guard('web')->logout();

                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return redirect('/login?session_expired=1');
            }

            return $response;
        });
    })->create();
