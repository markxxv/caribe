<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front;

//Route::get('/', function () {
//    return view('home');
//});

Route::get('/', [Front::class, 'index'])->name('home');
Route::get('/blog', [Front::class, 'blogIndex'])->name('blog.index');
Route::get('/blog/{slug}', [Front::class, 'blogShow'])->name('blog.show');
Route::get('/tours', [Front::class, 'toursIndex'])->name('tours.index');
Route::get('/tours/argentina', [Front::class, 'toursArgentina'])->name('tours.argentina');
Route::get('/tours/{slug}', [Front::class, 'toursShow'])->name('tours.show');

Route::view('/services', 'services')->name('services');
Route::view('/contact', 'contact')->name('contact');
