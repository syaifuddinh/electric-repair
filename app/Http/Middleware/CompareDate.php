<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;

class CompareDate
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
        if(!$request->filled('start_date')) {
            $request->start_date = '07-08-1945';
        }
        if(!$request->filled('end_date')) {
            $request->end_date = '07-08-2145';
        }
        if(isset($request->start_date) AND isset($request->end_date)) {
            $start = Carbon::parse($request->start_date);
            $end = Carbon::parse($request->end_date);

            if($start->gt($end)) {
                $request->start_date = $end->format('d-m-Y');
                $request->end_date = $start->format('d-m-Y');
            }
            else {
                return $next($request);
            }
        }
        
        return $next($request);
    }
}
