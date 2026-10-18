<?php

use Illuminate\Support\Facades\Route;

Route::get('ping', fn () => 'ok')->name('bare.ping');
