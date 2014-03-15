<?php

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

class Follow extends Eloquent 
{
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'followers';

	/**
	 * Disable timestamps when creating new follower
	 *
	 * @var string
	 */	
	public $timestamps = false;

	/**
	 * The attributes that can be mass assigned.
	 *
	 * @var string
	 */
	protected $fillable = array('follower', 'following');

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('id', 'follower', 'following');

	//Take note that Eloquent assumes the foreign key of the relationship based on the model name.
	//Other way is to specify the foreign key in the relation
	public function userFollowing()
	{
		return $this->hasOne('User', 'id', 'following');
	}
}