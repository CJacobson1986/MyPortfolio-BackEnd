<?php

use Illuminate\Http\Request;

//(Authentication)AuthenticateController
Route::resource('authenticate', 'AuthenticateController', ['only' => ['index']]);
Route::post('authenticate', 'AuthenticateController@authenticate');
Route::get('authenticate/user', 'AuthenticateController@getAuthenticatedUser');
Route::post('signUp', 'AuthenticateController@doSignUp');

//(Users)UsersController
Route::get('getUser/{name}', 'UsersController@getUser');
Route::post('updateProfile', 'UsersController@updateProfile');
Route::post('deactivateUser', 'UsersController@deactivateUser');
Route::get('getUsers', 'UsersController@getUsers');
Route::get('editUser/{id}', 'UsersController@editUser');
Route::post('deleteUser/{id}', 'UsersController@deleteUser');
Route::post('updateProfile/{id}', 'UsersController@updateProfile');

//(Admin)UsersController
Route::post('storeRole', 'UsersController@storeRole');
Route::get('editRole/{id}', 'UsersController@editRole');
Route::put('updateRole/{id}', 'UsersController@updateRole');
Route::post('deleteRole/{id}', 'UsersController@deleteRole');
Route::put('setRole/{id}', 'UsersController@setRole');
Route::get('banUser/{id}', 'UsersController@banUser');

//(Content)ContentController
Route::get('createTopic', 'ContentController@createTopic');
Route::post('storeTopic', 'ContentController@storeTopic');
Route::post('updateTopic/{id}','ContentController@updateTopic');
Route::post('deleteTopic/{id}', 'ContentController@deleteTopic');
//Route::get('setFeature/{id}', 'ContentController@setFeature');
Route::get('getDetail/{slug}', 'ContentController@getDetail');
Route::get('getInfo', 'ContentController@getInfo');
Route::get('main', 'ContentController@main');
//Route::get('getFeatured', 'ContentController@getFeatured');

//(Channels)RecomsController
Route::post('storeChannel', 'RecomsController@storeChannel');
Route::post('updateChannel/{id}', 'RecomsController@updateChannel');
Route::post('deleteChannel/{id}', 'RecomsController@deleteChannel');
Route::get('getChannels', 'RecomsController@getChannels');
Route::get('getChannel/{slug}', 'RecomsController@getChannel');

//(Reply)CommentController
Route::put('updateReply/{id}', 'CommentController@updateReply');
Route::post('deleteReply', 'CommentController@deleteReply');
Route::get('getReplies/{slug}', 'CommentController@getReplies');
Route::post('postReply', 'CommentController@storeReply');

//(Search)RecomsController
Route::post('search', 'RecomsController@search');
Route::get('getTopics/channel={channel}&count={count}', 'ContentController@getTopics');
Route::get('getNew/channel={channel}&count={count}', 'RecomsController@getNew');


//CatchAll
Route::any('{path?}', 'MainController@index')->where("path", ".+");
