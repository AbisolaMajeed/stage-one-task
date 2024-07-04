<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;


Route::get('/', function(){
    return "Welcome to ".config('app.name')." (Application Programming Interface)";
});

Route::get('/hello', [HomeController::class, 'getDetails']);
