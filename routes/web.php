<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//Route::get('/', function () { return view('welcome'); });
Route::get('/', 'App\Http\Controllers\HomeController@welcome');
Route::get('/signin', 'App\Http\Controllers\AuthController@signin');
Route::get('/callback', 'App\Http\Controllers\AuthController@callback');
Route::get('/signout', 'App\Http\Controllers\AuthController@signout');
