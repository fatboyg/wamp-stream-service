<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Contracts\Auth\Factory as AuthFactory;


class AuthenticateOnceWithBasicAuth
{
    public function __construct(AuthFactory $auth)
    {
        $this->auth = $auth;
    }

/**
* Handle an incoming request.
*
* @param  \Illuminate\Http\Request  $request
* @param  \Closure  $next
* @return mixed
*/
    public function handle($request, Closure $next, $guard = null)
{
    $tokens = [env('AUTH_API_USER_TOKEN1') => 'apiClient'];

    //        return $this->auth->guard($guard)->basic() ?: $next($request);

    if(!isset($tokens[$request->getPassword()]))
    {
        $headers = ['WWW-Authenticate' => 'Basic'];


        return new Response('Invalid credentials.', 401, $headers);
    }

    return $next($request);
}

}