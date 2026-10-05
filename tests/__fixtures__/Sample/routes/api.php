<?php

use Illuminate\Support\Facades\Route;

Route::get('sample-api', fn () => 'ok')->name('sample.api');
