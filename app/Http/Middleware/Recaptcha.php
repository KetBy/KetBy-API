<?php
 
namespace App\Http\Middleware;
 
use Closure;
use ReCaptcha\ReCaptcha as GoogleRecaptcha;
 
class Recaptcha
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = (new GoogleRecaptcha(env('RECAPTCHA_SECRET_KEY')))
            ->verify($request->input('g-recaptcha-response'), $request->ip());
 
        if (!$response->isSuccess()) {
            return response()->json(['success' => false, 'message' => 'Recaptcha failed. Please try again.']);
        }
 
        return $next($request);
    }
}