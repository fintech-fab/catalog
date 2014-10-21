<?php


namespace FintechFab\Catalog\Tests;

use CategoryAdmin;
use FintechFab\Catalog\Exceptions\CategoryException;
use FintechFab\Catalog\Models\Category;

class CategoryTest extends \TestCase
{


	public function setUp()
	{
		parent::setUp();

		Category::truncate();
	}

	public function testCreatePath()
	{

		$category = CategoryAdmin::create(array(
			'name' => 'Категория Номер Раз',
		))->get();

		/** @noinspection SpellCheckingInspection */
		$this->assertEquals('kategoriya-nomer-raz', $category->path);

	}

	public function testCreateLevel()
	{

		$category = $this->createCategory();
		$this->assertEquals(0, $category->level);
		$category = $this->createCategory($category->id);

		$this->assertEquals(1, $category->level);
		$this->assertEquals(1, $category->parent_id);

	}

	public function testCreateOrder()
	{

		$category = $this->createCategory();
		$this->assertEquals(1, $category->order);
		$category = $this->createCategory();
		$this->assertEquals(2, $category->order);

	}

	public function testParent()
	{

		$parent = $this->createCategory();
		$child = $this->createCategory($parent->id);

		$this->assertEquals($parent->id, $child->parent->id);
		$this->assertEquals($child->id, $parent->descendants[0]->id);

	}

	public function testPathFull_1()
	{

		$parent = $this->createCategory(0, 'раз');
		$child = $this->createCategory($parent->id, 'два');

		$this->assertEquals('raz/dva', $child->path_full);

	}

	public function testPathFull_2()
	{

		$one = $this->createCategory(0, 'раз');
		$two = $this->createCategory($one->id, 'два');
		$three = $this->createCategory($two->id, 'три');
		$four = $this->createCategory($three->id, 'четыре');

		$this->assertEquals('raz/dva/tri', $three->path_full);

		$result = CategoryAdmin::init($two)->update([
			'path' => 'two-edited',
		]);

		$this->assertNotFalse($result);

		$two = CategoryAdmin::get()->find($two->id);
		$this->assertEquals('raz/two-edited', $two->path_full);

		$three = CategoryAdmin::get()->find($three->id);
		$this->assertEquals('raz/two-edited/tri', $three->path_full);

		$four = CategoryAdmin::get()->find($four->id);
		$this->assertEquals('raz/two-edited/tri/chetire', $four->path_full);

	}

	public function testMove2Parent()
	{

		$root1 = $this->createCategory();
		$root2 = $this->createCategory();

		$child = CategoryAdmin::create(array(
			'name'      => 'Категория Номер Два',
			'parent_id' => $root2->id,
		))->move2Parent($root1->id)->get();

		$child = Category::find($child->id);
		$this->assertEquals($root1->id, $child->parent_id);

	}

	public function testMove2ParentWithOrder()
	{

		$r1 = $this->createCategory();
		$r2 = $this->createCategory();
		$this->createCategory();

		$c1_r1 = $this->createCategory($r1->id);
		$c2_r1 = $this->createCategory($r1->id);

		$c1_r2 = $this->createCategory($r2->id);
		$c2_r2 = $this->createCategory($r2->id);
		$c3_r2 = $this->createCategory($r2->id);

		CategoryAdmin::init($c1_r1)
			->move2Parent($r2->id)
			->moveAfter($c1_r2->id);

		list($c1_r1, $c2_r1, $c1_r2, $c2_r2, $c3_r2) = $this->rereadCategories([$c1_r1->id, $c2_r1->id, $c1_r2->id, $c2_r2->id, $c3_r2->id]);

		$this->assertCount(1, $r1->descendants);
		$this->assertCount(4, $r2->descendants);

		$this->assertEquals(1, $c2_r1->order);

		$this->assertEquals(1, $c1_r2->order);
		$this->assertEquals(2, $c1_r1->order);
		$this->assertEquals(3, $c2_r2->order);
		$this->assertEquals(4, $c3_r2->order);

	}

	public function testMove2ParentWithSymlink()
	{

		$r1 = $this->createCategory();
		$r2 = $this->createCategory();
		$r3 = $this->createCategory();
		$r2->symlink_id = $r3->id;
		$r2->save();

		CategoryAdmin::init($r2)->move2Parent($r1->id)->moveAfter(0);

	}

	public function testCreate2ParentWithSymlink()
	{

		$r1 = $this->createCategory();
		$r2 = $this->createCategory();
		$r1->symlink_id = $r2->id;
		$r1->save();

		$r3 = $this->createCategory();
		$result = CategoryAdmin::init($r3->id)->update(['parent_id' => $r1->id]);
		$this->assertFalse($result);

	}

	public function testMoveTreeConflict()
	{

		/*
		 *  n1_1
		 *      *
		 *      n2_2
		 *          $n3_1
		 *              $n4_1
		 *              *
		 *              *
		 *          *
		 *          *
		 *      *
		 */

		$n1_1 = $this->createCategory();

		$this->createCategory($n1_1->id);
		$n2_2 = $this->createCategory($n1_1->id);
		$this->createCategory($n1_1->id);

		$n3_1 = $this->createCategory($n2_2->id);
		$this->createCategory($n2_2->id);
		$this->createCategory($n2_2->id);

		$n4_1 = $this->createCategory($n3_1->id);
		$this->createCategory($n3_1->id);
		$this->createCategory($n3_1->id);

		try {
			CategoryAdmin::init($n2_2)->move2Parent($n2_2->id);
			$this->fail('CategoryException required here');
		} catch (CategoryException $e) {
			$this->assertEquals('tree conflict', $e->getMessage());
		}

		try {
			CategoryAdmin::init($n2_2)->move2Parent($n3_1->id);
			$this->fail('CategoryException required here');
		} catch (CategoryException $e) {
			$this->assertEquals('tree conflict', $e->getMessage());
		}

		try {
			CategoryAdmin::init($n2_2)->move2Parent($n4_1->id);
			$this->fail('CategoryException required here');
		} catch (CategoryException $e) {
			$this->assertEquals('tree conflict', $e->getMessage());
		}

	}

	public function testSymLink()
	{

		/*
		 *  n1_1
		 *      *
		 *      n2_2
		 *          n3_1
		 *              n4_1
		 *              *
		 *              *
		 *          *
		 *          *
		 *      *
		 *  n1_2
		 */

		$n1_1 = $this->createCategory();

		$this->createCategory($n1_1->id);
		$n2_2 = $this->createCategory($n1_1->id);
		$this->createCategory($n1_1->id);

		$n3_1 = $this->createCategory($n2_2->id);
		$this->createCategory($n2_2->id);
		$this->createCategory($n2_2->id);

		$n4_1 = $this->createCategory($n3_1->id);
		$this->createCategory($n3_1->id);
		$this->createCategory($n3_1->id);

		$n1_2 = $this->createCategory();

		// none
		$result = CategoryAdmin::init($n1_1)->update([
			'name'       => 'asdf',
			'symlink_id' => 0,
		]);
		$this->assertNotFalse($result);

		// self
		$result = CategoryAdmin::init($n1_1)->update([
			'name'       => 'asdf',
			'symlink_id' => $n1_1->id,
		]);
		$this->assertFalse($result);

		// new and self-parent
		$result = CategoryAdmin::init(0)->create([
			'name'       => 'asdf',
			'symlink_id' => 2,
			'parent_id'  => 2,
		]);
		$this->assertFalse($result);

		// new and self-parent chain
		$result = CategoryAdmin::init(0)->create([
			'name'       => 'asdf',
			'symlink_id' => $n2_2->id,
			'parent_id'  => $n4_1->id,
		]);
		$this->assertFalse($result);

		// new and valid link
		$result = CategoryAdmin::init(0)->create([
			'name'       => 'asdf',
			'symlink_id' => $n1_2->id,
			'parent_id'  => $n4_1->id,
		]);
		$this->assertnotFalse($result);

	}

	public function testMoveOrderSubLevel()
	{

		$category = $this->createCategory();
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->createCategories($category->id, 5);

		$this->assertEquals(1, $sub2->order);
		$this->assertEquals(2, $sub3->order);
		$this->assertEquals(3, $sub4->order);
		$this->assertEquals(4, $sub5->order);
		$this->assertEquals(5, $sub6->order);

		CategoryAdmin::init($sub4)->moveAfter($sub2->id);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub2->order);
		$this->assertEquals(2, $sub4->order);
		$this->assertEquals(3, $sub3->order);
		$this->assertEquals(4, $sub5->order);
		$this->assertEquals(5, $sub6->order);

		CategoryAdmin::init($sub4)->moveAfter($sub5->id);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub2->order);
		$this->assertEquals(2, $sub3->order);
		$this->assertEquals(3, $sub5->order);
		$this->assertEquals(4, $sub4->order);
		$this->assertEquals(5, $sub6->order);

		CategoryAdmin::init($sub4)->moveAfter(0);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub4->order);
		$this->assertEquals(2, $sub2->order);
		$this->assertEquals(3, $sub3->order);
		$this->assertEquals(4, $sub5->order);
		$this->assertEquals(5, $sub6->order);

		CategoryAdmin::init($sub3)->moveAfter($sub6->id);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub4->order);
		$this->assertEquals(2, $sub2->order);
		$this->assertEquals(3, $sub5->order);
		$this->assertEquals(4, $sub6->order);
		$this->assertEquals(5, $sub3->order);

		CategoryAdmin::init($sub3)->moveAfter(0);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub3->order);
		$this->assertEquals(2, $sub4->order);
		$this->assertEquals(3, $sub2->order);
		$this->assertEquals(4, $sub5->order);
		$this->assertEquals(5, $sub6->order);

		CategoryAdmin::init($sub3)->moveAfter($sub6->id);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub4->order);
		$this->assertEquals(2, $sub2->order);
		$this->assertEquals(3, $sub5->order);
		$this->assertEquals(4, $sub6->order);
		$this->assertEquals(5, $sub3->order);

		CategoryAdmin::init($sub2)->moveAfter(0);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub2->order);
		$this->assertEquals(2, $sub4->order);
		$this->assertEquals(3, $sub5->order);
		$this->assertEquals(4, $sub6->order);
		$this->assertEquals(5, $sub3->order);

		CategoryAdmin::init($sub2)->moveAfter($sub4->id);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub4->order);
		$this->assertEquals(2, $sub2->order);
		$this->assertEquals(3, $sub5->order);
		$this->assertEquals(4, $sub6->order);
		$this->assertEquals(5, $sub3->order);

		CategoryAdmin::init($sub6)->moveAfter($sub3->id);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub4->order);
		$this->assertEquals(2, $sub2->order);
		$this->assertEquals(3, $sub5->order);
		$this->assertEquals(4, $sub3->order);
		$this->assertEquals(5, $sub6->order);

		CategoryAdmin::init($sub3)->moveAfter($sub6->id);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub4->order);
		$this->assertEquals(2, $sub2->order);
		$this->assertEquals(3, $sub5->order);
		$this->assertEquals(4, $sub6->order);
		$this->assertEquals(5, $sub3->order);

		CategoryAdmin::init($sub5)->moveAfter($sub2->id);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub4->order);
		$this->assertEquals(2, $sub2->order);
		$this->assertEquals(3, $sub5->order);
		$this->assertEquals(4, $sub6->order);
		$this->assertEquals(5, $sub3->order);

		CategoryAdmin::init($sub3)->moveAfter($sub6->id);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub4->order);
		$this->assertEquals(2, $sub2->order);
		$this->assertEquals(3, $sub5->order);
		$this->assertEquals(4, $sub6->order);
		$this->assertEquals(5, $sub3->order);

		CategoryAdmin::init($sub4)->moveAfter(0);
		list($sub2, $sub3, $sub4, $sub5, $sub6) = $this->rereadCategories([$sub2->id, $sub3->id, $sub4->id, $sub5->id, $sub6->id]);

		$this->assertEquals(1, $sub4->order);
		$this->assertEquals(2, $sub2->order);
		$this->assertEquals(3, $sub5->order);
		$this->assertEquals(4, $sub6->order);
		$this->assertEquals(5, $sub3->order);

	}

	/**
	 * @param int $parent_id
	 * @param int $cnt
	 *
	 * @return Category[]
	 */
	private function createCategories($parent_id, $cnt = 5)
	{
		$list = array();
		for ($i = 0; $i < $cnt; $i++) {
			$list[] = CategoryAdmin::create(array(
				'name'      => 'Под Категория',
				'parent_id' => $parent_id,
			))->get();
		}

		return $list;
	}

	/**
	 * @param array $ids
	 *
	 * @return Category[]
	 */
	private function rereadCategories(array $ids)
	{
		$list = array();
		foreach ($ids as $id) {
			$list[] = Category::find($id);
		}

		return $list;
	}

	private function createCategory($parent_id = 0, $name = 'Категория')
	{
		return CategoryAdmin::create(array(
			'name'      => $name,
			'parent_id' => $parent_id,
		))->get();
	}

}
 