<?php

use App\Http\Controllers\ProductController;
use App\Livewire\ProductCatalog;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.homepage')->name('home');
// Route::view('/products', 'pages.product-catalog')->name('product-catalog');
Route::get('/products', ProductCatalog::class)->name('product-catalog');
// Route::view('/product', 'pages.product')->name('product');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product');
Route::view('/cart', 'pages.cart')->name('cart');
Route::view('/checkout', 'pages.checkout')->name('checkout');
Route::view('/order-confirmed', 'pages.order-confirmed')->name('order-confirmed');
Route::view('/page', 'pages.page')->name('page');
