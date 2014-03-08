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


/**
 * All users in the database
 *
 * @return Json representation of users
*/	
Route::get('/user/', function()
{
	return Response::json(User::all());
});


/**
 * Gets specific user by id
 *
 * @var user_id 
 * @return Json representation of user
*/	
Route::get('/user/{user_id}/', function($user_id)
{
	return Response::json(User::find($user_id));
});


/**
 * Searches database by the query string username for users
 *
 * @var username 
 * @return Json representation of a list of users
*/	
Route::get('/user/{username}/search', function($username)
{
	return Response::json(User::where('username', 'like', '%'.$username.'%')->get());
});


/**
 * Checks if username exists in database
 *
 * @var username 
 * @return Json representation if user exists
*/	
Route::get('/user/{username}/exists', function($username)
{
	$userCount = User::where('username', $username)->count();
	return Response::json(array('user_available' => $userCount == 0));
});


/**
 * Gets basic user information and all his posts
 *
 * @var user_id 
 * @return Json representation of user info and posts
*/	
Route::get('/user/{user_id}/info', function($user_id)
{
	$user = User::findOrFail($user_id);

	$posts = Post::with('user', 'likes.user', 'comments.user')
				   ->where('user_id', $user_id)
				   ->orderBy('created_at', 'desc')
				   ->get();

	$response = array('user' => $user->toArray(), 
					  'posts' => $posts->toArray());

	return Response::json($response);
});


/**
 * Inserts a new user into the database if requirements are fulfilled
 * Post data is on the form {"username":"input_username", "password":"input_password"}
 * 
 * @return Json representation if user exists
*/	
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

		$user_data = array(
			'username' => $username, 
			'password' => $password,
			'first_name' => Input::get('first_name'),
			'last_name' => Input::get('last_name'),
			'about' => Input::get('about'),
			'avatar' => Input::get('avatar')
		);

		$user = User::create($user_data);

		return JsonSuccess('New user created');
	}
});


/**
 * Update user data in database, access_token needs to be sent with request
 * Post data is on the form {"first_name":"input_firstname", ...etc}
 * 
 * @return Json representation if user exists
*/	
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
	return Post::with('user', 'likes.user', 'comments.user')
				 ->orderBy('created_at', 'desc')
				 ->get();
});

Route::get('/post/{post_id}/', function($post_id)
{
	return Post::with('user', 'likes.user', 'comments.user')->find($post_id);
});

Route::delete('/post/{post_id}/{access_token}', function($post_id, $access_token)
{
	//TODO: Added authentication through url, not the best way or the restful way
	//Look into this, works though

	$token = Token::where('key', $access_token)->firstOrFail();

	$post = Post::find($post_id);

	$postCreator = $post->user->id;

	if($postCreator == $token->user->getKey())
	{
		$post->delete();
		return JsonSuccess("Deleted post with id=".$post_id);
	}
	else
		return JsonError("Couldn't delete post with id=".$post_id);
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
| Like Post Routes
|--------------------------------------------------------------------------
*/


/**
 * Like Post with id $post_id
 * Json request  is on the form {"access_token":"input_access_token"}
 * 
 * @var $post_id
 * @return Json response
*/	
Route::post('/post/like/{post_id}', function($post_id)
{
	$access_token = Input::get('access_token');

	$token = Token::where('key', $access_token)->firstOrFail();
	
	$post_data = array(
				 'user_id' => $token->user->getKey(),
				 'post_id' => $post_id);

	$like = PostLikes::create($post_data);

	return Response::json($like->with('user')
							   ->where('id', $like->id)
							   ->first());
});

/*
|--------------------------------------------------------------------------
| Comment Post Routes
|--------------------------------------------------------------------------
*/


/**
 * Comment on Post with id $post_id
 * Json request is on the form {"access_token":"input_access_token", "comment":"input_comment"}
 * 
 * @var $post_id
 * @return Json response
*/	
Route::post('/post/comment/{post_id}', function($post_id)
{
	$access_token = Input::get('access_token');
	$comment = Input::get("comment");

	$token = Token::where('key', $access_token)->firstOrFail();

	$post_data = array(
		'user_id' => $token->user->getKey(),
		'post_id' => $post_id,
		'comment' => $comment
	);

	$comment = PostComments::create($post_data);

	return Response::json($comment->with('user')
				   ->where('id', $comment->id)
				   ->first());
});


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