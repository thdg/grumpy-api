<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableFollow extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('followers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('follower')->unsigned()->foreign()->references('id')->on('users');
			$table->integer('following')->unsigned()->foreign()->references('id')->on('users');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('followers');
	}

}
