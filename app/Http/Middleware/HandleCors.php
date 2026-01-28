<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\HandleCors as Middleware;

class HandleCors extends Middleware
{
    protected $allowedOrigins = ['*']; // Your Flutter web domain
    protected $allowedMethods = ['*'];
    protected $allowedHeaders = ['*'];
}
