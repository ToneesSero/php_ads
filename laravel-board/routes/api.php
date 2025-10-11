<?php

use App\Http\Controllers\Api\ListingApiController;
use Illuminate\Support\Facades\Route;

Route::get('/listings', [ListingApiController::class, 'index']);
