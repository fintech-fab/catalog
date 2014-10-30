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


	public function testRemove()
	{

		$product = $this->createProduct();
		$category1 = $this->createCategory();
		$category2 = $this->createCategory();
		$category3 = $this->createCategory();

		ProductAdmin::add2Category([$category1->id, $category2->id, $category3->id]);
		ProductAdmin::removeFromCategory([$category1->id, $category3->id]);

		$categories = $product->categories;
		$this->assertCount(1, $categories);
		$this->assertEquals($category2->id, $categories[0]->id);

	}

	public function testClear()
	{

		$product = $this->createProduct();
		$category1 = $this->createCategory();
		$category2 = $this->createCategory();
		$category3 = $this->createCategory();

		ProductAdmin::add2Category([$category1->id, $category2->id, $category3->id]);
		ProductAdmin::clearCategory([$category1->id, $category3->id]);

		$categories = $product->categories;
		$this->assertCount(1, $categories);
		$this->assertEquals($category2->id, $categories[0]->id);

	}

	public function testChange()
	{

		$product1 = $this->createProduct();
		$product2 = $this->createProduct();
		$product3 = $this->createProduct();

		$category1 = $this->createCategory();
		$category2 = $this->createCategory();
		$category3 = $this->createCategory();

		ProductAdmin::init($product1)->add2Category([$category1, $category2]);
		ProductAdmin::init($product2)->add2Category([$category1, $category2]);
		ProductAdmin::init($product3)->add2Category([$category1, $category3]);

		ProductAdmin::changeBatchCategory([$product2, $product3], $category1->id, $category3->id);

		$categories = $product2->categories;
		$this->assertCount(2, $categories);
		$this->assertEquals($category2->id, $categories[0]->id);
		$this->assertEquals($category3->id, $categories[1]->id);

		$categories = $product3->categories;
		$this->assertCount(1, $categories);
		$this->assertEquals($category3->id, $categories[0]->id);

	}

	public function testDefaultSorting()
	{

		$product1 = $this->createProduct();
		$product2 = $this->createProduct();
		$product3 = $this->createProduct();

		$category = $this->createCategory();

		ProductAdmin::init($product2)->add2Category($category->id);
		ProductAdmin::init($product1)->add2Category($category->id);
		ProductAdmin::init($product3)->add2Category($category->id);

		$list = ProductAdmin::listByCategory($category->id);
		$this->assertEquals($product2->id, $list[0]->id);
		$this->assertEquals($product1->id, $list[1]->id);
		$this->assertEquals($product3->id, $list[2]->id);


		$rel = ProductCategoryRel::existing($product1->id, $category->id)->first();
		$this->assertEquals(2, $rel->order);

		$rel = ProductCategoryRel::existing($product2->id, $category->id)->first();
		$this->assertEquals(1, $rel->order);

		$rel = ProductCategoryRel::existing($product3->id, $category->id)->first();
		$this->assertEquals(3, $rel->order);

	}

	public function testRemoveSorting()
	{

		$product1 = $this->createProduct();
		$product2 = $this->createProduct();
		$product3 = $this->createProduct();
		$product4 = $this->createProduct();
		$product5 = $this->createProduct();

		$category = $this->createCategory();

		ProductAdmin::init($product1)->add2Category($category->id);
		ProductAdmin::init($product2)->add2Category($category->id);
		ProductAdmin::init($product3)->add2Category($category->id);
		ProductAdmin::init($product4)->add2Category($category->id);
		ProductAdmin::init($product5)->add2Category($category->id);

		ProductAdmin::init($product2)->removeFromCategory($category->id);
		ProductAdmin::init($product4)->removeFromCategory($category->id);

		$rel = ProductCategoryRel::existing($product1->id, $category->id)->first();
		$this->assertEquals(1, $rel->order);

		$rel = ProductCategoryRel::existing($product3->id, $category->id)->first();
		$this->assertEquals(2, $rel->order);

		$rel = ProductCategoryRel::existing($product5->id, $category->id)->first();
		$this->assertEquals(3, $rel->order);

	}

	public function testBringToOrderCategory()
	{

		$product1 = $this->createProduct();
		$product2 = $this->createProduct();
		$product3 = $this->createProduct();

		$category = $this->createCategory();

		ProductAdmin::init($product2)->add2Category($category->id);
		ProductAdmin::init($product1)->add2Category($category->id);
		ProductAdmin::init($product3)->add2Category($category->id);

		ProductAdmin::init($product3)->orderCategory2Top($category->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product3->id, $list[0]->id);
		$this->assertEquals($product2->id, $list[1]->id);
		$this->assertEquals($product1->id, $list[2]->id);

		ProductAdmin::init($product2)->orderCategory2Top($category->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product2->id, $list[0]->id);
		$this->assertEquals($product3->id, $list[1]->id);
		$this->assertEquals($product1->id, $list[2]->id);

		ProductAdmin::init($product3)->orderCategory2Bottom($category->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product2->id, $list[0]->id);
		$this->assertEquals($product1->id, $list[1]->id);
		$this->assertEquals($product3->id, $list[2]->id);

		ProductAdmin::init($product2)->orderCategory2Bottom($category->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product1->id, $list[0]->id);
		$this->assertEquals($product3->id, $list[1]->id);
		$this->assertEquals($product2->id, $list[2]->id);

	}

	public function testBringToOrderCategoryAfter()
	{

		$product1 = $this->createProduct();
		$product2 = $this->createProduct();
		$product3 = $this->createProduct();
		$product4 = $this->createProduct();
		$product5 = $this->createProduct();

		$category = $this->createCategory();

		ProductAdmin::init($product1)->add2Category($category->id);
		ProductAdmin::init($product2)->add2Category($category->id);
		ProductAdmin::init($product3)->add2Category($category->id);
		ProductAdmin::init($product4)->add2Category($category->id);
		ProductAdmin::init($product5)->add2Category($category->id);

		ProductAdmin::init($product3)->moveAfter($category->id, $product3->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product1->id, $list[0]->id);
		$this->assertEquals($product2->id, $list[1]->id);
		$this->assertEquals($product3->id, $list[2]->id);
		$this->assertEquals($product4->id, $list[3]->id);
		$this->assertEquals($product5->id, $list[4]->id);

		ProductAdmin::init($product2)->moveAfter($category->id, $product5->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product1->id, $list[0]->id);
		$this->assertEquals($product3->id, $list[1]->id);
		$this->assertEquals($product4->id, $list[2]->id);
		$this->assertEquals($product5->id, $list[3]->id);
		$this->assertEquals($product2->id, $list[4]->id);

		ProductAdmin::init($product3)->moveAfter($category->id, 0);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product3->id, $list[0]->id);
		$this->assertEquals($product1->id, $list[1]->id);
		$this->assertEquals($product4->id, $list[2]->id);
		$this->assertEquals($product5->id, $list[3]->id);
		$this->assertEquals($product2->id, $list[4]->id);

		ProductAdmin::init($product2)->moveAfter($category->id, $product1->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product3->id, $list[0]->id);
		$this->assertEquals($product1->id, $list[1]->id);
		$this->assertEquals($product2->id, $list[2]->id);
		$this->assertEquals($product4->id, $list[3]->id);
		$this->assertEquals($product5->id, $list[4]->id);

		ProductAdmin::init($product3)->moveAfter($category->id, $product5->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product1->id, $list[0]->id);
		$this->assertEquals($product2->id, $list[1]->id);
		$this->assertEquals($product4->id, $list[2]->id);
		$this->assertEquals($product5->id, $list[3]->id);
		$this->assertEquals($product3->id, $list[4]->id);

	}

	public function testBatchOrderCategoryAfter()
	{

		$product1 = $this->createProduct();
		$product2 = $this->createProduct();
		$product3 = $this->createProduct();
		$product4 = $this->createProduct();
		$product5 = $this->createProduct();

		$category = $this->createCategory();

		ProductAdmin::init($product1)->add2Category($category->id);
		ProductAdmin::init($product2)->add2Category($category->id);
		ProductAdmin::init($product3)->add2Category($category->id);
		ProductAdmin::init($product4)->add2Category($category->id);
		ProductAdmin::init($product5)->add2Category($category->id);

		ProductAdmin::moveBatchAfter([$product3->id, $product5->id], $product1->id, $category->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product1->id, $list[0]->id);
		$this->assertEquals($product3->id, $list[1]->id);
		$this->assertEquals($product5->id, $list[2]->id);
		$this->assertEquals($product2->id, $list[3]->id);
		$this->assertEquals($product4->id, $list[4]->id);

		ProductAdmin::moveBatchAfter([$product3->id, $product5->id, $product2->id], 0, $category->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product3->id, $list[0]->id);
		$this->assertEquals($product5->id, $list[1]->id);
		$this->assertEquals($product2->id, $list[2]->id);
		$this->assertEquals($product1->id, $list[3]->id);
		$this->assertEquals($product4->id, $list[4]->id);

		ProductAdmin::moveBatchAfter([$product5->id, $product1->id], $product4->id, $category->id);
		$list = ProductAdmin::listByCategory($category->id);

		$this->assertEquals($product3->id, $list[0]->id);
		$this->assertEquals($product2->id, $list[1]->id);
		$this->assertEquals($product4->id, $list[2]->id);
		$this->assertEquals($product5->id, $list[3]->id);
		$this->assertEquals($product1->id, $list[4]->id);

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
 