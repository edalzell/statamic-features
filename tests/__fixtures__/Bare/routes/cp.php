<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->get('bare-cp', fn () => 'ok')->name('bare.cp');
