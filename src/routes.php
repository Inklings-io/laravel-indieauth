<?php

Route::group(['middleware' => ['web']], function() {
    Route::post('indieauth_login', 'Inklings\IndieAuth\AuthController@login');
    Route::post('indieauth_login/complete', 'Inklings\IndieAuth\AuthController@login_complete');
    Route::post('indieauth_login/token', 'Inklings\IndieAuth\AuthController@token');
    Route::post('indieauth_logout', 'Inklings\IndieAuth\AuthController@logout');
});

