<?php

namespace App\Http\Middleware;

use App\Services\UserActivityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageActivity
{
    // Route name prefixes that are not meaningful module entry points
    protected array $skipRoutePatterns = [
        'livewire.',
        'debugbar.',
        'telescope.',
        'horizon.',
        'sanctum.',
        'prints.',      // print-preview routes (not navigation)
    ];

    // URL path segments that indicate non-page requests
    protected array $skipUrlSegments = [
        'livewire',
        '_debugbar',
        'telescope',
        'horizon',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET requests; skip Livewire AJAX wire updates
        if ($request->method() !== 'GET' || $request->hasHeader('X-Livewire')) {
            return $response;
        }

        if (!auth()->check()) {
            return $response;
        }

        $routeName = $request->route()?->getName() ?? '';

        foreach ($this->skipRoutePatterns as $pattern) {
            if (str_starts_with($routeName, $pattern)) {
                return $response;
            }
        }

        $path = $request->path();
        foreach ($this->skipUrlSegments as $segment) {
            if (str_contains($path, $segment)) {
                return $response;
            }
        }

        $user = auth()->user();

        defer(fn () => UserActivityService::trackPageView($user, $request));

        return $response;
    }
}
