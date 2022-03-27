<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Auth;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        // \Log::error($exception);
        return parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof \PDOException) {
          $messages = explode(" ",$exception->getMessage());
          if (in_array('SQLSTATE[45000]:',$messages)) {
            return response()->json(['message' => 'Jurnal tidak dapat diinput karena periode transaksi sudah di closing'],400);
          }
        }
        $rendered = parent::render($request, $exception);
        $msgServer = [];
        $status_code = $rendered->getStatusCode();
        if (!env('APP_DEBUG')) {
            if($rendered->getStatusCode() == 302) {
                $url = url()->current();
                if(is_numeric(strpos($url, '/api/')) ) {
                    $msgServer = 'Waktu login anda telah habis. Silahkan lakukan login ulang';
                    $status_code = 401;
                } else {
                    return $rendered;
                }
            }

            if ($rendered->getStatusCode() == 500) {
                $msgServer = trans('messages-error.500');
            } else if ($rendered->getStatusCode() == 405 || $rendered->getStatusCode() == 404) {
                $msgServer = trans('messages-error.404-405');
            } else if ($rendered->getStatusCode() == 403) {
                $msgServer = trans('messages-error.403');
            } else if ($rendered->getStatusCode() == 401) {
                $msgServer = trans('messages-error.401');
            } else if ($rendered->getStatusCode() == 400) {
                $msgServer = trans('messages-error.400');
            } else if ($rendered->getStatusCode() == 422) {
              return parent::render($request, $exception);
            } else {
                $msgServer = trans('messages-error.unknown');
            }
        } else {
            $url = url()->current();
            if(is_numeric(strpos($url, '/api/')) ) {
                if($rendered->getStatusCode() != 200) {
                    if($rendered->getStatusCode() == 404) {
                        $status_code = $rendered->getStatusCode();
                        $msgServer = 'URL not found';
                    } else {
                        $origin = get_class($exception);
                        if(strpos($origin, 'ValidationException')) {
                            $errors = $rendered->original['errors'] ?? [];
                            if(is_array($errors)) {
                                if(count($errors) > 0) {
                                    foreach($errors as $e) {
                                        $msgServer = $e[0] ?? $exception->getMessage();
                                        break;
                                    }
                                } else {
                                    $msgServer = $exception->getMessage();
                                }
                            } else {
                                $msgServer = $exception->getMessage();
                            }
                        } else {
                            $msgServer = $exception->getMessage();
                        }
                        $status_code = 421;
                    }
                }

            } else {
                return $rendered;
            }

        }
        return response()->json([
            'message' => $msgServer, 
            'error' => $exception->getMessage(),
            'data' => null
        ], $status_code);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.','status' => 'ERROR', 'data' => null], 401);
        }
        return redirect()->guest(route('login'));
    }
}
