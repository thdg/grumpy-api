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
	return Response::json(array('message' => $message, 'status_message' => 'failed', 'status' => false ));
};

function JsonSuccess($message)
{
	return Response::json(array('message' => $message, 'status_message' => 'success', 'status' => true ));
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

Route::get('/user/{username}/search', function($username)
{
	return Response::json(User::where('username', 'like', '%'.$username.'%')->get());
});

Route::get('/user/{username}/exists', function($username)
{
	$userCount = User::where('username', $username)->count();
	return Response::json(array('user_available' => $userCount == 0));
});

Route::post('/user/', function()
{
	if ( !(Input::has('username') || Input::has('password')) )
	{
		return JsonError('Request must provide both username and password');
	}
	
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

		$first_name = Input::get('first_name');
		$last_name = Input::get('last_name');
		$about = Input::get('about');
		$avatar = Input::get('avatar');

		$user_data = array(
			'username' => $username, 
			'password' => $password,
			'first_name' => $first_name,
			'last_name' => $last_name,
			'about' => $about,
			'avatar' => $avatar
		);

		$user = User::create($user_data);

		return JsonSuccess('New user created');
	}
});

Route::put('/user/', function()
{
	$access_token = Input::get('access_token');

	$token = Token::where('key', $access_token)->firstOrFail();

	$userid = $token->user->getKey();

	$user = User::find($userid);

	if (Input::has("first_name"))
		$user->first_name = Input::get("first_name");

	if (Input::has("last_name"))
		$user->last_name = Input::get("last_name");

	if (Input::has("email"))
		$user->email = Input::get("email");

	if (Input::has("birthday"))
		$user->birthday = Input::get("birthday");

	if (Input::has("about"))
		$user->about = Input::get("about");

	if (Input::has("avatar"))
		$user->avatar = Input::get("avatar");

	$user->update();

	return JsonSuccess("Updated user successfully");
});

/*
|--------------------------------------------------------------------------
| Post Routes
|--------------------------------------------------------------------------
*/

Route::get('/post/', function()
{
	return Post::with('user')->orderBy('created_at', 'desc')->get();
});

Route::get('/post/{post_id}/', function($post_id)
{
	return Post::with('user')->find($post_id);
});

Route::delete('/post/{post_id}/', function($post_id)
{
	//We'll need to add some authentication here, retrofit does not allow to set body
	//on delete method
	Post::destroy($post_id);

	return JsonSuccess("Deleted post with id=".$post_id);
});

Route::post('/post/', array('before' => 'token', function()
{
	$access_token = Input::get('access_token');
	$text = Input::get('post');

	$token = Token::where('key', $access_token)->firstOrFail();

	$post_data = array(
		'user_id' => $token->user->getKey(), 
		'post' => $text
	);
	$post = Post::create($post_data);

	return JsonSuccess('New post created');
}));

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/login/', function()
{
	if ( !(Input::has('username') || Input::has('password')) )
	{
		return JsonError('Request must provide both username and password');
	}
	
	$username = Input::get('username');
	$password = Input::get('password');

	if (Auth::attempt(array('username' => $username, 'password' => $password))) 
	{
		$access_token = md5(rand());
		$user = User::where('username', $username)->firstOrFail();
		$token = Token::create(array('user_id' => $user->getKey(), 'key' => $access_token, 'expire_date' => time() + (7*24*60*60)));
	   
	    $response = array(
	    	'message' => 'User logged in', 
	    	'access_token' => $token->getToken(), 
	    	'user' => $user->toArray(),
	    	'status' => TRUE);

		return Response::json($response);
	} 
	else 
	{
	    return JsonError('Log in failed');
	}
});

Route::post('/logout/', function()
{
	$access_token = Input::get('access_token');
	$token = Token::where('key', $access_token)->firstOrFail();
	$token->deactivate();

	return JsonSuccess('User logged out');
});