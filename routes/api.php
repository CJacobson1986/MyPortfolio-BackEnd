<?php

use Illuminate\Http\Request;

    Route::get('getInfo', 'RecomsController@getInfo');

    Route::resource('authenticate', 'AuthenticateController', ['only' => ['index']]);
    Route::post('authenticate', 'AuthenticateController@authenticate');
    Route::get('authenticate/user', 'AuthenticateController@getAuthenticatedUser');
    Route::post('signUp', 'AuthenticateController@doSignUp');
    Route::post('confirmToken', 'AuthenticateController@confirmToken');
    Route::post('resetPassword', 'AuthenticateController@resetPassword');
    Route::post('confirmReset/{token}', 'AuthenticateController@confirmReset');
    Route::get('refreshToken', 'AuthenticateController@refreshToken');

    Route::get('main', 'RecomsController@main');
    Route::get('getTopics/channel={channel}&count={count}', 'RecomsController@getTopics');
    Route::get('getNew/channel={channel}&count={count}', 'RecomsController@getNew');
    Route::get('getFeatured', 'RecomsController@getFeatured');
    Route::get('getDetail/{slug}', 'RecomsController@getDetail');
    Route::get('getReplies/{slug}', 'RecomsController@getReplies');
    Route::post('postReply', 'RecomsController@storeReply');

    Route::get('getChannels', 'RecomsController@getChannels');
    Route::get('getChannel/{slug}', 'RecomsController@getChannel');

    Route::get('getUser/{name}', 'RecomsController@getUser');
    Route::post('updateProfile', 'RecomsController@updateProfile');
    Route::post('deactivateUser', 'RecomsController@deactivateUser');

    //Users
    Route::get('getUsers', 'UsersController@getUsers');
    Route::get('editUser/{id}', 'UsersController@editUser');
    Route::post('deleteUser/{id}', 'UsersController@deleteUser');
    Route::get('banUser/{id}', 'UsersController@banUser');
    Route::post('updateProfile/{id}', 'UsersController@updateProfile');
    Route::post('addRole', 'UsersController@storeRole');
    Route::get('editRole/{id}', 'UsersController@editRole');
    Route::put('updateRole/{id}', 'UsersController@updateRole');
    Route::post('deleteRole/{id}', 'UsersController@deleteRole');
    Route::put('setRole/{id}', 'UsersController@setRole');

    //Content
    Route::get('createTopic', 'RecomsController@createTopic');
    Route::post('postTopic', 'RecomsController@storeTopic');
    Route::post('updateTopic/{id}','RecomsController@updateTopic');
    Route::post('deleteTopic/{id}', 'RecomsController@deleteTopic');
    Route::get('setFeature/{id}', 'RecomsController@setFeature');
    Route::post('postChannel', 'RecomsController@storeChannel');
    Route::post('updateChannel/{id}', 'RecomsController@updateChannel');
    Route::post('deleteChannel/{id}', 'RecomsController@deleteChannel');
    Route::put('updateReply/{id}', 'RecomsController@updateReply');
    Route::post('deleteReply', 'RecomsController@deleteReply');

    Route::post('search', 'RecomsController@search');
Route::any('{path?}', 'MainController@index')->where("path", ".+");
