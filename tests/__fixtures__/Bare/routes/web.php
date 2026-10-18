<?php

use Illuminate\Support\Facades\Route;

Route::get('bare-web', fn () => 'ok')->name('bare.web');
