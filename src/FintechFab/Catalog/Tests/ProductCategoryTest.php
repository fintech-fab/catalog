<?php


namespace FintechFab\Catalog\Tests;

use CategoryAdmin;
use FintechFab\Catalog\Models\Category;
use FintechFab\Catalog\Models\Product;
use FintechFab\Catalog\Models\ProductCategoryRel;
use ProductAdmin;

class ProductCategoryTest extends \TestCase
{


	public function setUp()
	{
		parent::setUp();

		Product::truncate();
		Category::truncate();
		ProductCategoryRel::truncate();
	}


	public function testMany2Many()
	{

		$product = $this->createProduct();
		$category1 = $this->createCategory();
		$category2 = $this->createCategory();

		ProductAdmin::add2Category([
			$category1->id,
			$category2->id,
		]);

		$categories = $product->categories;
		$this->assertCount(2, $categories);
		$this->assertEquals($category1->id, $categories[0]->id);
		$this->assertEquals($category2->id, $categories[1]->id);

	}

	private function createCategory($parent_id = 0, $name = 'Категория')
	{
		return CategoryAdmin::create(array(
			'name'      => $name,
			'parent_id' => $parent_id,
		))->get();
	}

	private function createProduct($name = 'Продукт')
	{
		return ProductAdmin::create(array(
			'name' => $name,
		))->get();
	}

}
 