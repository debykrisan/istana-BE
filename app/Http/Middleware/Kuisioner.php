<?php

namespace App\Http\Middleware;

use Closure;

class Kuisioner
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */


    public function handle($request, Closure $next)
    {
        $apiKey = $request->header('x-access-token');

        $allowedApiKey = '1TGcvT0ulyhZ3PM3FBrwQrlHGy64KVPuEQx7b73uBvg4LkvwWsI17fB0U5cQPkAn';

        if ($apiKey !== $allowedApiKey) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }
        
        return $next($request);
    }
}
