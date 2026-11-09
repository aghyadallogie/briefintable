<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Statamic\Exceptions\NotFoundHttpException as StatamicNotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // A plain abort(404) would show resources/views/errors/404.antlers.html as a bare fragment (no layout,
        // navbar or styles). Statamic's own exception renders it inside the site layout, and in the control
        // panel and API in their own formats, so every 404 is routed through it here. Its own exceptions
        // (unknown URLs, unpublished entries) already render that way, and JSON clients keep Laravel's JSON.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($e instanceof StatamicNotFoundHttpException || $request->expectsJson()) {
                return null;
            }

            return (new StatamicNotFoundHttpException($e->getMessage(), $e))->render($request);
        });
    })->create();
