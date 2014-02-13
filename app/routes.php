<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/

Route::get('/user/', function()
{
	return User::all();
});

Route::get('/user/{user_id}/', function($user_id)
{
	return User::find($user_id);
});

Route::post('/user/', function()
{
	$username = Input::get('username');
	$username_taken = User::where('username',$username)->count();
	if ($username_taken>0) 
	{
		$response = array('message' => 'Username already taken', 'status' => 'failed');
	}
	else 
	{
		$password = Input::get('password');
		$password = Hash::make($password);

		$user_data = array(
			'username' => $username, 
			'password' => $password,
		);

		$user = User::create($user_data);

		$response = array('message' => 'New user created', 'status' => 'success');
	}

	return json_encode($message);
});

/*
|--------------------------------------------------------------------------
| Post Routes
|--------------------------------------------------------------------------
*/

Route::post('/post/', array('before' => 'token', function()
{
	$creator = Auth::user()->id;
	$text = Input::get('text');

	$post_data = array(
		'creator' => $creator, 
		'text' => $text
	);

	$post = Post::create($post_data);

	$response = array('message' => 'New post created', 'status' => 'success');
	return json_encode($message);
}));

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/login/', function()
{
	$username = Input::get('username');
	$password = Input::get('password');

	if (Auth::attempt(array('username' => $username, 'password' => $password), true)) 
	{
		$token = Token::create();
	    $response = array('message' => 'User logged in', 'access_token' => $token->getKey(), 'status' => 'success');
	} 
	else 
	{
	    $response = array('message' => 'User not logged in', 'status' => 'failed');
	}

	return json_encode($response);
});

Route::post('/logout/', function()
{
	$access_token = Input::get('access_token');
	$token = Token::where('key', $access_token)->get();
	$token->deactivate();

	$response = array('message' => 'User logged out', 'status' => 'success');
	return json_encode($response);
});