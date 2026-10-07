<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->alias([
            'admin'                => \App\Http\Middleware\AdminMiddleware::class,
            'whatsapp.signature'   => \App\Http\Middleware\VerifyWhatsAppSignature::class,
        ]);

        // Stripe sends raw POST requests without a CSRF token — exclude webhook endpoint
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'Your session has expired. Please refresh the page and try again.',
                    ], 419);
                }

                $target = $request->is('login') ? route('login') : url()->previous(route('login'));

                return redirect()->to($target)
                    ->withInput($request->except(['password', 'password_confirmation', '_token']))
                    ->with('status', 'Your session expired. Please sign in again.');
            }
        });
    })->create();
