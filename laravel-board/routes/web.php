<?php

use App\Http\Controllers\ListingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/listings');

Route::resource('listings', ListingController::class);
