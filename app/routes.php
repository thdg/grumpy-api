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

Route::get('/userexists/{username}/', function($username)
{
	$userCount = User::where('username', '=' , $username)->count();

	//TODO: Look into making this response more general
	if ($userCount > 0)
		return Response::json(array('user_available' => false));
	else
		return Response::json(array("user_available" => true));
});

Route::post('/user/', function()
{
	if (Input::has('username') && Input::has('password'))
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
	}
	else
	{
		$response = array('message' => 'Request must provide both username and password', 'status' => 'failed');
	}

	return json_encode($response);
});

/*
|--------------------------------------------------------------------------
| Post Routes
|--------------------------------------------------------------------------
*/

Route::get('/post/', function()
{
	return Post::all();
});

Route::get('/post/{post_id}/', function($post_id)
{
	return Post::find($post_id);
});


Route::post('/post/', array('before' => 'token', function()
{
	$access_token = Input::get('access_token');
	$token = Token::where('key', $access_token)->firstOrFail();
	$creator = $token->user;
	$text = Input::get('text');

	if (!$creator)
	{
		$response = array('message' => 'Currupted access token, no user found', 'status' => 'failed', 'token'=> $token->user, 'user'=> $creator);
		return json_encode($response);
	}

	$post_data = array(
		'creator' => $creator, 
		'post' => $text
	);

	$post = Post::create($post_data);

	$response = array('message' => 'New post created', 'status' => 'success');
	return json_encode($response);
}));

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/login/', function()
{
	if (Input::has('username') && Input::has('password'))
	{
		$username = Input::get('username');
		$password = Input::get('password');

		if (Auth::attempt(array('username' => $username, 'password' => $password), true)) 
		{
			$access_token = md5(rand());
			$user = User::where('username', $username)->firstOrFail();
			$token = Token::create(array('user' => $user, 'key' => $access_token, 'expire_date' => time() + (7*24*60*60)));
		    $response = array('message' => 'User logged in', 'access_token' => $token->getKey(), 'status' => 'success');
		} 
		else 
		{
		    $response = array('message' => 'User not logged in', 'status' => 'failed');
		}
	}
	else
	{
		$response = array('message' => 'Request must provide both username and password', 'status' => 'failed');
	}

	return json_encode($response);
});

Route::post('/logout/', function()
{
	$access_token = Input::get('access_token');
	$token = Token::where('key', $access_token)->firstOrFail();
	$token->deactivate();

	$response = array('message' => 'User logged out', 'status' => 'success');
	return json_encode($response);
});