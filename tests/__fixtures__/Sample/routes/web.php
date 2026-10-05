<?php

use Illuminate\Support\Facades\Route;

Route::get('sample-web', fn () => 'ok')->name('sample.web');
