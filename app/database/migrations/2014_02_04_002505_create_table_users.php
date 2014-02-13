<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableUsers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->timestamps();
			$table->string('username', 32)->unique();
			$table->string('password');

			$table->string('first_name', 32)->nullable();
			$table->string('last_name', 32)->nullable();
			$table->string('email')->nullable();

			$table->date('birthday')->nullable();
			$table->string('about')->nullable();
			$table->string('avatar')->nullable();

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}

}
