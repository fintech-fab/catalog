<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class Products extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{

		Schema::connection('ff-cat')->create('products', function (Blueprint $table) {

			$table->increments('id');
			$table->unsignedInteger('category_id')->default(0);
			$table->unsignedInteger('product_type_id')->default(0);
			$table->string('name')->default('');
			$table->string('code')->default('');
			$table->string('sid')->default('');
			$table->string('path')->default('');
			$table->boolean('enabled')->default(0);
			$table->boolean('deleted')->default(0);
			$table->unsignedInteger('order')->default(0);
			$table->timestamps();

		});

		Schema::connection('ff-cat')->create('variations', function (Blueprint $table) {

			$table->increments('id');
			$table->unsignedInteger('product_id')->default(0);
			$table->string('name')->default('');
			$table->string('code')->default('');
			$table->string('sid')->default('');
			$table->boolean('enabled')->default(0);
			$table->boolean('deleted')->default(0);
			$table->unsignedInteger('order')->default(0);
			$table->timestamps();

		});

		Schema::connection('ff-cat')->create('product_types', function (Blueprint $table) {

			$table->increments('id');
			$table->string('name')->default('');
			$table->string('code')->default('');
			$table->string('sid')->default('');
			$table->string('path')->default('');

			$table->unsignedInteger('order')->default(0);

			$table->timestamps();

		});

		Schema::connection('ff-cat')->create('product_tags', function (Blueprint $table) {

			$table->increments('id');
			$table->string('name')->default('');
			$table->string('path')->default('');

		});

		Schema::connection('ff-cat')->create('product_tag_rel', function (Blueprint $table) {

			$table->unsignedInteger('product_id')->default(0);
			$table->unsignedInteger('product_tag_id')->default(0);

		});

		Schema::connection('ff-cat')->create('product_category_rel', function (Blueprint $table) {

			$table->unsignedInteger('product_id')->default(0);
			$table->unsignedInteger('category_id')->default(0);
			$table->unsignedInteger('order')->default(0);

		});

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::connection('ff-cat')->drop('products');
		Schema::connection('ff-cat')->drop('variations');
		Schema::connection('ff-cat')->drop('product_types');
		Schema::connection('ff-cat')->drop('product_tags');
		Schema::connection('ff-cat')->drop('product_tag_rel');
		Schema::connection('ff-cat')->drop('product_category_rel');
	}

}
