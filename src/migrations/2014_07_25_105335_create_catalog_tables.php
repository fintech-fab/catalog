<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCatalogTables extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

		Schema::connection('ff-cat')->create('categories', function (Blueprint $table) {

			$table->increments('id');

			$table->unsignedInteger('parent_id')->default(0);
			$table->unsignedInteger('symlink_id')->default(0);
			$table->unsignedInteger('category_type_id')->default(0);

			$table->string('name')->default('');
			$table->string('code')->default('');
			$table->string('sid')->default('');
			$table->string('path')->default('');
            $table->string('path_full')->default('');
			$table->unsignedInteger('left')->default(0);
			$table->unsignedInteger('right')->default(0);

			$table->boolean('enabled')->default(0);
			$table->boolean('deleted')->default(0);

			$table->unsignedInteger('level')->default(0);
			$table->unsignedInteger('order')->default(0);

			$table->timestamps();

		});

		Schema::connection('ff-cat')->create('category_types', function (Blueprint $table) {

			$table->increments('id');
			$table->string('name')->default('');
			$table->string('code')->default('');
			$table->string('sid')->default('');
			$table->string('path')->default('');

			$table->unsignedInteger('order')->default(0);

			$table->timestamps();

		});

		Schema::connection('ff-cat')->create('category_tags', function (Blueprint $table) {

			$table->increments('id');
			$table->string('name')->default('');
			$table->string('path')->default('');

		});

		Schema::connection('ff-cat')->create('category_tag_rel', function (Blueprint $table) {

			$table->unsignedInteger('category_id')->default(0);
			$table->unsignedInteger('category_tag_id')->default(0);

		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::connection('ff-cat')->drop('categories');
		Schema::connection('ff-cat')->drop('category_types');
		Schema::connection('ff-cat')->drop('category_tags');
		Schema::connection('ff-cat')->drop('category_tag_rel');
	}

}
