<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/products/search/{search?}', 'App\Http\Controllers\SearchController@search')->name('search');
Route::get('/products/preorders/search/{search?}', 'App\Http\Controllers\SearchController@search')->name('search');
