<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetTimeoutForLivewire
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Set longer timeout for Livewire update requests
        if ($request->hasHeader('X-Livewire') || $request->is('livewire/update')) {
            // Set timeout to 60 seconds for Livewire requests
            set_time_limit(60);
            
            // Set max execution time and memory limit
            ini_set('max_execution_time', 60);
            ini_set('memory_limit', '256M');
            
            // Add headers to prevent caching of dynamic content
            $response = $next($request);
            
            if (method_exists($response, 'header')) {
                $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
                $response->header('Pragma', 'no-cache');
                $response->header('Expires', '0');
            }
            
            return $response;
        }

        return $next($request);
    }
}