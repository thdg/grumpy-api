<?php

class Token extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'tokens';

	/**
	 * The attributes that can be mass assigned.
	 *
	 * @var string
	 */
	protected $fillable = array('user', 'expire_date', 'key', 'active');

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('key');
	/**
	 * Get the access_token.
	 *
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * See if token is expired, if it is and still active it is deactivated.
	 *
	 * @return boolean
	 */
	public function isExpired()
	{
		$expired = $this->expire_date<time();
		if ($expired && $this->active) $this->deactivate();
		return $expired;
	}

	/**
	 * See if token is actve.
	 *
	 * @return boolean
	 */
	public function isActive()
	{
		return $this->active;
	}

	/**
	 * Deactivate token.
	 *
	 * @return void
	 */
	public function deactivate()
	{
		$this->active = false;
		$this->save();
	}

}