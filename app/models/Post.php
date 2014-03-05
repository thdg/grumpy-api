<?php

class Post extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'posts';

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('user_id');

	/**
	 * The attributes that can be mass assigned.
	 *
	 * @var string
	 */
	protected $fillable = array('user_id', 'post');

	public function user()
	{
		return $this->belongsTo('User');
	}

	public function likes()
	{
		return $this->hasMany('PostLikes');
	}

	public function comments()
	{
		return $this->hasMany('PostComments');
	}
}