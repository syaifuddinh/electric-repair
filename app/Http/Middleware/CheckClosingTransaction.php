<?php

namespace App\Http\Middleware;
use Carbon\Carbon;
use App\Model\TypeTransaction;
use App\Model\Closing;

use Closure;

class CheckClosingTransaction
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $slug)
    {
        $requestMethod = $request->method();
        $methods = ['POST', 'UPDATE', 'DELETE', 'PUT'];

        $month = new Carbon();
        $startPeriode = $month->copy()->startOfMonth()->format('Y-m-d');
        $endPeriode = $month->copy()->endOfMonth()->format('Y-m-d');
        $closing = Closing::where('start_periode', $startPeriode)->where('end_periode', $endPeriode)->where('status', 1)->where('company_id', auth()->user()->company_id)->first();

        $typeTransaction = TypeTransaction::where('is_lock', true)->where('slug', $slug)->whereBetween('last_date_lock', [$startPeriode, $endPeriode])->first();

        if (!empty($typeTransaction) && in_array($requestMethod, $methods) && !empty($closing))
        {
          return response(['errors' => ['Transaksi sudah di closing']], 422);
        }

        return $next($request);
    }
}
