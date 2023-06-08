<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
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
Route::get('/popout', function (Request $request) {
    $ou = $request->ou;
    return view('popout', ['launchUrl' => url("/signin?ou=$ou")]);
});
Route::get('/signin', 'App\Http\Controllers\AuthController@signin');
Route::get('/callback', 'App\Http\Controllers\AuthController@callback');
Route::get('/signout', 'App\Http\Controllers\AuthController@signout');

Route::post('/add', 'App\Http\Controllers\HomeController@addStudentView');
Route::post('/remove', 'App\Http\Controllers\HomeController@removeStudentView');
