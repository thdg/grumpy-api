<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id')->primary();
			$table->timestamps();
			$table->string('username', 32)->unique();
			$table->string('email')->unique();
			$table->string('salt', 16);

			$table->string('first_name', 32);
			$table->string('last_name', 32);

			$table->date('birthday');
			$table->string('about');
			$table->string('avatar');

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
