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

function JsonError($message)
{
	return Response::json(array('message' => $message, 'status' => 'failed' ));
};


/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/

Route::get('/user/', function()
{
	return Response::json(User::all());
});

Route::get('/user/{user_id}/', function($user_id)
{
	return Response::json(User::find($user_id));
});

Route::get('/userexists/{username}/', function($username)
{
	$userCount = User::where('username', $username)->count();
	return Response::json(array('user_available' => $userCount == 0));
});

Route::post('/user/', function()
{
	if (Input::has('username') && Input::has('password'))
	{
		$username = Input::get('username');
		$username_taken = User::where('username',$username)->count();
		if ($username_taken>0) 
		{
			return JsonError('Username already taken');
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

			return Response::json(array('message' => 'New user created', 'status' => 'success'));
		}
	}
	else
	{
		return JsonError('Request must provide both username and password');
	}
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
	return Response::json($posts);
});

Route::get('/post/{post_id}/', function($post_id)
{
	$post = Post::find($post_id);
	$post['user'] = $post->user;

	return Response::json($post);
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

	return Response::json(array('message' => 'New post created', 'status' => 'success'));
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
			return Response::json($response);
		} 
		else 
		{
		    return JsonError('Log in failed');
		}
	}
	else
	{
		return JsonError('Request must provide both username and password');
	}
});

Route::post('/logout/', function()
{
	$access_token = Input::get('access_token');
	$token = Token::where('key', $access_token)->firstOrFail();
	$token->deactivate();

	$response = array('message' => 'User logged out', 'status' => 'success');
	return Response::json($response);
});