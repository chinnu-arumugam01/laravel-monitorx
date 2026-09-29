<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectMonitorX
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        $username = (string) config('monitorx.username');
        $password = (string) config('monitorx.password');
        if ($username === '' || $password === '') {
            abort(403, 'MonitorX access credentials are not configured.');
        }
        if (! hash_equals($username, (string) $request->getUser()) || ! hash_equals($password, (string) $request->getPassword())) {
            return response('MonitorX authentication required.', 401, ['WWW-Authenticate' => 'Basic realm="MonitorX"']);
        }

        return $next($request);
    }
}
