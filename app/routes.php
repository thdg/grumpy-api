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

function CustomJsonResponse($message, $status)
{
	$reponse = array('message' => $message, 
			         'status' => $status );
	
	return Response::json($response);
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
}
)->where('user_id', '[0-9]+');


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
}
)->where('user_id', '[0-9]+');


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
		return CustomJsonResponse('Request must provide both username and password', false);
	}
	
	$username = Input::get('username');
	$username_taken = User::where('username',$username)->count();
	if ($username_taken>0) 
	{
		return CustomJsonResponse('Username already taken', false);
	}
	else 
	{
		$base64_image = Input::get('base64image');
	
		$image = public_path().'/img/user'.$username.'.jpg';
		file_put_contents($image, base64_decode($base64_image));

		$avatarLocation = 'http://arnarh.com/grumpy/public/img/user'.$username.'.jpg';

		$password = Input::get('password');
		$password = Hash::make($password);

		$user_data = array(
			'username' => $username, 
			'password' => $password,
			'first_name' => Input::get('first_name'),
			'last_name' => Input::get('last_name'),
			'about' => Input::get('about'),
			'avatar' => $avatarLocation
		);

		$user = User::create($user_data);

		return CustomJsonResponse('New user created', true);	
	}
});


/**
 * Update user data in database, access_token needs to be sent with request
 * Post data is on the form {"first_name":"input_firstname", ...etc}
 * 
 * @return Json representation if user exists
*/	
Route::put('/user/', array('before' => 'token', function()
{
	$access_token = Request::header('Authorization');

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

	return CustomJsonResponse('Updated user successfully', true);
}));

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
}
)->where('post_id', '[0-9]+');

Route::delete('/post/{post_id}', array('before' => 'token', function($post_id)
{
	$access_token = Request::header('Authorization');

	$token = Token::where('key', $access_token)->firstOrFail();

	$post = Post::find($post_id);

	$postCreator = $post->user->id;

	if($postCreator == $token->user->getKey())
	{
		$post->delete();
		return CustomJsonResponse("Deleted post with id=".$post_id, true);
	}
	else
		return CustomJsonResponse("Couldn't delete post with id=".$post_id, false);
})
)->where('post_id', '[0-9]+');;


Route::post('/post/', array('before' => 'token', function()
{
	$access_token = Request::header('Authorization');
	$text = Input::get('post');

	$token = Token::where('key', $access_token)->firstOrFail();

	$post_data = array(
		'user_id' => $token->user->getKey(), 
		'post' => $text
	);
	
	$post = Post::create($post_data);

	return CustomJsonResponse('New post created', true);
}));


Route::get('/post/following/{user_id}', function($user_id)
{
	$following = DB::table('followers')
			   		->where('follower', $user_id)
			   		->select('following')
			   		->lists('following');

	return Post::with('user', 'likes.user', 'comments.user')
				 ->whereIn('user_id', $following)
				 ->orderBy('created_at', 'desc')
				 ->get();
}
)->where('user_id', '[0-9]+');


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
Route::post('/post/like/{post_id}', array('before' => 'token', function($post_id)
{
	$access_token = Request::header('Authorization');

	$token = Token::where('key', $access_token)->firstOrFail();
	
	$post_data = array(
				 'user_id' => $token->user->getKey(),
				 'post_id' => $post_id);

	$like = PostLikes::create($post_data);

	return Response::json($like->with('user')
							   ->where('id', $like->id)
							   ->first());
})
)->where('post_id', '[0-9]+');


/*
|--------------------------------------------------------------------------
| Unlike Post Routes
|--------------------------------------------------------------------------
*/


/**
 * Unlike Post with id $like_id
 * Json request  is on the form {"access_token":"input_access_token"}
 * 
 * @var $like_id
 * @return Json response
*/	
Route::delete('/post/like/{like_id}', array('before' => 'token', function($like_id)
{
	$access_token = Request::header('Authorization');
	$token = Token::where('key', $access_token)->firstOrFail();

	$like = PostLikes::find($post_id);

	$postCreator = $like->user->id;

	if($postCreator == $token->user->getKey())
	{
		$post->delete();
		return CustomJsonResponse("Deleted like with id=".$post_id, true);
	}
	else
		return CustomJsonResponse("Couldn't delete like with id=".$post_id, false);
})
)->where('post_id', '[0-9]+');;

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
Route::post('/post/comment/{post_id}', array('before' => 'token', function($post_id)
{
	$access_token = Request::header('Authorization');
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
})
)->where('post_id', '[0-9]+');


/*
|--------------------------------------------------------------------------
| Follow User Routes
|--------------------------------------------------------------------------
*/


/**
 * Follow user with id $user_id
 * Json request is on the form {"access_token":"input_access_token"}
 * 
 * @var $user_id
 * @return Json response
*/	
Route::post('/follow/{user_id}', array('before' => 'token', function($user_id)
{
	$access_token = Request::header('Authorization');

	$token = Token::where('key', $access_token)->firstOrFail();

	$follow_data = array(
		'follower' => $token->user->getKey(),
		'following' => $user_id
	);

	$following = Follow::create($follow_data);

	return CustomJsonResponse('Followed user with id='.$user_id, true);
})
)->where('user_id', '[0-9]+');;


/**
 * Get Followers for user with id = $user_id
 * 
 * @return Json response
*/	
Route::get('/follow/{user_id}/', function($user_id)
{
	$following = Follow::with('userFollowing')
						->where('follower', $user_id)
						->get();
	
	return Response::json($following);
}
)->where('user_id', '[0-9]+');;


/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/login/', function()
{
	if ( !(Input::has('username') || Input::has('password')) )
	{
		return CustomJsonResponse('Request must provide both username and password', false);
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
		return CustomJsonResponse('Log in failed', false);
});

Route::post('/logout/', function()
{
	$access_token = Request::header('Authorization');
	$token = Token::where('key', $access_token)->firstOrFail();
	$token->deactivate();

	return CustomJsonResponse('User logged out', true);
});