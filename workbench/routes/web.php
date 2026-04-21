<?php

use Illuminate\Support\Facades\Route;
use Workbench\App\Models\User;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::get('/users', fn () => response()->json(User::query()->get()));

Route::get('/', function () {
    return view('welcome');
});
