<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RespondToQueuedForm
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->header('X-Lentera-Async') === '1' && ! $request->isMethod('GET') && $response instanceof RedirectResponse) {
            $json = response()->json(['redirect' => $response->getTargetUrl()])->header('Cache-Control', 'private, no-store');
            foreach ($response->headers->getCookies() as $cookie) {
                $json->headers->setCookie($cookie);
            }

            return $json;
        }

        return $response;
    }
}
