<?php

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'App\Http\Controllers\API'], function () {
    // --------------- Register and Login ----------------//
    Route::post('register', 'AuthenticationController@register')->name('register');
    Route::post('login', 'AuthenticationController@login')->name('login');

    // ------------------ Get Data & User Management ----------------------//
    Route::middleware('auth:api')->group(function () {
        Route::get('get-user-info', 'AuthenticationController@userInfo')->name('get-user-info');
        Route::post('logout', 'AuthenticationController@logOut')->name('logout');
        
        // Routes pour la gestion des utilisateurs (Admin & SuperAdmin uniquement)
        Route::middleware('role:admin,superadmin')->group(function () {
            Route::get('users', 'AuthenticationController@getUsers')->name('get-users');
            Route::post('users/{user}/assign-role', 'AuthenticationController@assignRole')->name('assign-role');
            Route::post('users/{user}/ban', 'AuthenticationController@banUser')->name('ban-user');
            Route::post('users/{user}/reward', 'AuthenticationController@rewardUser')->name('reward-user');
        });

        // Routes pour la gestion des publications (Admin & SuperAdmin uniquement)
        Route::middleware('role:admin,superadmin')->group(function () {
            Route::post('posts', 'PostController@createPost')->name('create-post');
        });
        
        // Routes pour les publications accessibles à tous les utilisateurs connectés
        Route::get('posts', 'PostController@getPosts')->name('get-posts');
        Route::post('posts/{post}/like', 'PostController@likePost')->name('like-post');
        Route::post('posts/{post}/comment', 'PostController@commentPost')->name('comment-post');

        // Routes pour la gestion des groupes de discussion (Admin & SuperAdmin uniquement)
        Route::middleware('role:admin,superadmin')->group(function () {
            Route::post('groups', 'GroupController@createGroup')->name('create-group');
            Route::post('groups/{group}/add-member', 'GroupController@addMember')->name('add-member');
        });
    });
});