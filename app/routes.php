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
	$posts = Post::all();
	foreach ($posts as $post)
	{
		$post['user'] = $post->user;
	}
	return $posts;
});

Route::get('/post/{post_id}/', function($post_id)
{
	$post = Post::find($post_id);
	$post['user'] = $post->user;

	return $post;
});


Route::post('/post/', array('before' => 'token', function()
{
	$access_token = Input::get('access_token');
	$text = Input::get('text');

	$token = Token::where('key', $access_token)->firstOrFail();

	$post_data = array(
		'user_id' => $token->user->getKey(), 
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
			$token = Token::create(array('user_id' => $user->getKey(), 'key' => $access_token, 'expire_date' => time() + (7*24*60*60)));
		    $response = array('message' => 'User logged in', 'access_token' => $token->getToken(), 'status' => 'success');
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