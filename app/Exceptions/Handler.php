<?php
namespace App\Exceptions;

use Throwable;
use App\Traits\ApiException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    use ApiException;

    public function register(): void
    {
        $this->renderable(function(Throwable $e){
            return ApiException::apiException($e);
        });
    }
}