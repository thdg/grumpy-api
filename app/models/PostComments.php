<?php

class PostComments extends Eloquent
 {
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'postcomments';

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('user_id', 'post_id');

	/**
	 * The attributes that can be mass assigned.
	 *
	 * @var string
	 */
	protected $fillable = array('user_id', 'post_id', 'comment');

	public function post()
	{
		return $this->belongsTo('Post');
	}

	public function user()
	{
		return $this->belongsTo('User');
	}
}