<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\RealestateController;
use App\Http\Controllers\SitemapController;

//Route::get('/', function () {
//    return view('home');
//});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/', [Front::class, 'index'])->name('home');
Route::get('/blog', [Front::class, 'blogIndex'])->name('blog.index');
Route::get('/blog/{filters}', [Front::class, 'blogIndex'])
    ->where('filters', '(?:country|search|page)/.+')->name('blog.filtered');
Route::get('/blog/{slug}', [Front::class, 'blogShow'])->name('blog.show');
Route::get('/tours', [Front::class, 'toursIndex'])->name('tours.index');
Route::get('/tours/argentina', [Front::class, 'toursArgentina'])->name('tours.argentina');
Route::get('/tours/argentina/{filters}', [Front::class, 'toursArgentina'])
    ->where('filters', '(?:search|page)/.+')->name('tours.argentina.filtered');
Route::get('/tours/{filters}', [Front::class, 'toursIndex'])
    ->where('filters', '(?:country|search|page)/.+')->name('tours.filtered');
Route::get('/tours/{slug}', [Front::class, 'toursShow'])->name('tours.show');

// Контент, перенесённый из Craft: URL совпадают со старым сайтом.
Route::get('/news', [ContentController::class, 'index'])->defaults('type', 'news')->name('news.index');
Route::get('/news/{filters}', [ContentController::class, 'index'])
    ->where('filters', '(?:country|search|page)/.+')->defaults('type', 'news')->name('news.filtered');
Route::get('/news/{slug}', [ContentController::class, 'show'])->defaults('type', 'news')->name('news.show');
Route::get('/articles/{category}/{filters}', [ContentController::class, 'index'])
    ->where('filters', '(?:country|search|page)/.+')->defaults('type', 'article')->name('article.category.filtered');
Route::get('/articles/{filters}', [ContentController::class, 'index'])
    ->where('filters', '(?:country|search|page)/.+')->defaults('type', 'article')->name('article.filtered');
Route::get('/articles/{category?}', [ContentController::class, 'index'])->defaults('type', 'article')->name('article.index');
Route::get('/article/{slug}', [ContentController::class, 'show'])->defaults('type', 'article')->name('article.show');
Route::get('/recipes', [ContentController::class, 'index'])->defaults('type', 'recipes')->name('recipes.index');
Route::get('/recipes/category/{category}', [ContentController::class, 'index'])
    ->where('category', '[a-z0-9]+(?:-[a-z0-9]+)*')->defaults('type', 'recipes')->name('recipes.category');
Route::get('/recipes/category/{category}/{filters}', [ContentController::class, 'index'])
    ->where('category', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->where('filters', '(?:search|page)/.+')->defaults('type', 'recipes')->name('recipes.category.filtered');
Route::get('/recipes/{filters}', [ContentController::class, 'index'])
    ->where('filters', '(?:search|page)/.+')->defaults('type', 'recipes')->name('recipes.filtered');
Route::get('/recipes/{slug}', [ContentController::class, 'show'])->defaults('type', 'recipes')->name('recipes.show');

Route::get('/realestate', [RealestateController::class, 'index'])->name('realestate.index');
// The constrained catch-all allows readable filter paths without hijacking property slugs.
Route::get('/realestate/{filters}', [RealestateController::class, 'index'])
    ->where('filters', '(?:buy|rent|in|type|currency|price|area|beds|baths|ref|newbuild|resale|sort|page)(?:/.*)?')
    ->name('realestate.filtered');
Route::get('/realestate/{url}', [RealestateController::class, 'show'])->name('realestate.show');

Route::view('/services', 'services')->name('services');
Route::view('/contact', 'contact')->name('contact');
