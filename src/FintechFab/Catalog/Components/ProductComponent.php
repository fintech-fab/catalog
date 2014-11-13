<?php


namespace FintechFab\Catalog\Components;

use FintechFab\Catalog\Helpers\Core;
use FintechFab\Catalog\Models\Category;
use FintechFab\Catalog\Models\Product;
use FintechFab\Catalog\Models\ProductCategoryRel;
use FintechFab\Catalog\Models\ProductTag;
use FintechFab\Catalog\Models\ProductTagRel;
use FintechFab\Catalog\Models\ProductType;
use Illuminate\Support\MessageBag;

class ProductComponent
{


	/**
	 * @var Category
	 */
	private $category;

	/**
	 * @var ProductCategoryRel
	 */
	private $categoryRel;

	/**
	 * @var Product
	 */
	private $product;

	/**
	 * @var ProductType
	 */
	private $type;

	/**
	 * @var ProductTag
	 */
	private $tag;

	/**
	 * @var ProductTagRel
	 */
	private $tagRel;

	/**
	 * @var \Illuminate\Validation\Validator
	 */
	private $validator;


	private $createInput = ['name'];
	private $updateInput = ['category_id', 'product_type_id', 'name', 'code', 'sid', 'path'];
	private $beforeState;

	public function __construct(
		Product $product,
		Category $category,
		ProductType $type,
		ProductTag $tag,
		ProductTagRel $tagRel,
		ProductCategoryRel $categoryRel
	)
	{
		$this->product = $product;
		$this->category = $category;
		$this->type = $type;
		$this->tag = $tag;
		$this->tagRel = $tagRel;
		$this->categoryRel = $categoryRel;
	}

	/**
	 * @param array $item
	 *
	 * @return ProductComponent|false
	 */
	public function create(array $item)
	{
		$item = $this->onlyCreateInput($item);
		$this->prepareRequiredData($item);
		$item['order'] = $this->calculateOrder($item['category_id']);
		$product = $this->product->newInstance($item);
		$this->init($product);

		if (!$this->validate($item)) {
			return false;
		}

		$product->save();

		$this->product = $product->find($product->id);

		return $this;

	}

	/**
	 * @return \FintechFab\Catalog\Models\Product
	 */
	public function get()
	{
		return $this->product;
	}

	/**
	 * @param integer|array|\FintechFab\Catalog\Models\Category $categories
	 *
	 * @return ProductComponent
	 */
	public function add2Category($categories = [])
	{

		$this->ob2id($categories);
		$this->categoryRel->addProduct2Category($this->get()->id, $categories);

		return $this;

	}

	public function categoryQuantity()
	{

		return $this->categoryRel
			->selectRaw("category_id, COUNT(*) as cnt")
			->groupBy('category_id')
			->get()->lists('cnt', 'category_id');

	}

	/**
	 * @param integer|array $categories
	 *
	 * @return ProductComponent
	 */
	public function removeFromCategory($categories = [])
	{
		$id = $this->get()->id;
		$this->ob2id($categories);

		$this->lockRel();
		foreach ($categories as $category_id) {
			$rel = $this->categoryRel->existing($id, $category_id)->first();
			$this->categoryRel
				->whereCategoryId($category_id)
				->where('order', '>', $rel->order)
				->update([
					'order' => $this->categoryRel->getConnection()->raw('`order`-1')
				]);
			$this->categoryRel->removeProductFromCategory($id, $category_id);
		}
		$this->unlock();

		return $this;

	}

	/**
	 * @param integer|array $categories
	 *
	 * @return ProductComponent
	 */
	public function clearCategory($categories = [])
	{

		$this->ob2id($categories);
		$this->categoryRel->clearCategory($categories);

		return $this;

	}


	private function prepareRequiredData(array &$item)
	{

		$item['path'] = empty($item['path'])
			? ''
			: trim($item['path']);

		if (empty($item['path']) && !empty($item['name'])) {
			$item['path'] = Core::translit4url($item['name']);
		}

		if (empty($item['category_id'])) {
			$item['category_id'] = 0;
		}

	}

	/**
	 *
	 * calculate order for all products
	 *
	 * @param $category_id
	 *
	 * @return int|mixed
	 */
	private function calculateOrder($category_id)
	{

		/** @var Product $cat */
		$product = $this->product->newInstance();
		$max = $product->whereCategoryId($category_id)->max('order');
		if (!$max) {
			$max = 0;
		}

		return $max + 1;

	}

	/**
	 * @param \FintechFab\Catalog\Models\Product|integer $product
	 *
	 * @return ProductComponent
	 */
	public function init($product)
	{
		if ($product === 0) {
			$this->product = $this->product->newInstance();
		} elseif (!is_object($product)) {
			$this->product = $this->product->newInstance()->find($product);
		} else {
			$this->product = $product;
		}

		$this->beforeState = ($this->product->id)
			? $this->product->getAttributes()
			: null;

		return $this;
	}

	private function validate(array &$item)
	{

		$rules = [
			'name' => 'required',
		];

		$this->validator = \Validator::make($item, $rules);

		return !$this->validator->fails();

	}

	public function errors()
	{
		if ($this->validator && $this->validator->errors()) {
			return $this->validator->errors();
		}

		return new MessageBag();

	}

	public function toggleEnable()
	{
		$this->product->toggleEnabled();
	}

	public function remove()
	{
		$this->product->remove();
	}

	/**
	 * list of product types
	 *
	 * @return array
	 */
	public function typeList()
	{
		return $this->type->get(['id', 'name'])->lists('name', 'id');
	}

	/**
	 * products by category
	 *
	 * @param integer $category_id
	 *
	 * @return \FintechFab\Catalog\Models\Product[]
	 */
	public function listByCategory($category_id)
	{

		return $this->categoryRel
			->where($this->categoryRel->getTable() . '.category_id', $category_id)
			->leftJoin(
				$this->product->getTable(), 'id', '=', 'product_id'
			)
			->orderBy($this->categoryRel->getTable() . '.order')
			->get()
			->all();

	}

	/**
	 * order product to top position into one category relation
	 *
	 * @param integer $category_id
	 *
	 * @return ProductComponent
	 */
	public function orderCategory2Top($category_id)
	{

		$currentRel = $this->categoryRel->existing($this->get()->id, $category_id)->first();

		$this->lockRel();
		$this->categoryRel
			->whereCategoryId($currentRel->category_id)
			->where('order', '<', $currentRel->order)
			->update([
				'order' => $this->categoryRel->getConnection()->raw('`order`+1')
			]);

		$currentRel->setOrder(1);
		$this->unlock();

		return $this;

	}

	/**
	 * order product to bottom position into one category relation
	 *
	 * @param integer $category_id
	 *
	 * @return ProductComponent
	 */
	public function orderCategory2Bottom($category_id)
	{

		$currentRel = $this->categoryRel->existing($this->get()->id, $category_id)->first();

		$this->lockRel();
		$this->categoryRel
			->whereCategoryId($currentRel->category_id)
			->where('order', '>', $currentRel->order)
			->update([
				'order' => $this->categoryRel->getConnection()->raw('`order`-1')
			]);

		$max = $this->categoryRel->maxCategoryOrder($currentRel->category_id);
		$currentRel->setOrder($max + 1);
		$this->unlock();

		return $this;

	}

	/**
	 * move product after other product into one category relation
	 *
	 * @param integer $category_id
	 * @param integer $id
	 *
	 * @return ProductComponent
	 */
	public function moveAfter($category_id, $id)
	{

		if ($id == 0) {
			return $this->orderCategory2Top($category_id);
		}

		$currentRel = $this->categoryRel->existing($this->get()->id, $category_id)->first();

		if ($this->get()->id == $id) {
			return $this;
		}

		$afterRel = $this->categoryRel->existing($id, $category_id)->first();

		$this->lockRel();
		if ($currentRel->order > $afterRel->order) {

			$this->categoryRel
				->whereCategoryId($currentRel->category_id)
				->where('order', '<', $currentRel->order)
				->where('order', '>', $afterRel->order)
				->update([
					'order' => $this->categoryRel->getConnection()->raw('`order`+1')
				]);
			$currentRel->setOrder($afterRel->order + 1);

		} else {

			$this->categoryRel
				->whereCategoryId($currentRel->category_id)
				->where('order', '>', $currentRel->order)
				->where('order', '<=', $afterRel->order)
				->update([
					'order' => $this->categoryRel->getConnection()->raw('`order`-1')
				]);
			$currentRel->setOrder($afterRel->order);

		}
		$this->unlock();

		return $this;

	}

	/**
	 * @param array   $list
	 * @param integer $id
	 * @param integer $category_id
	 *
	 * @return ProductComponent
	 */
	public function moveBatchAfter($list, $id, $category_id)
	{

		$this->ob2id($list);

		if (0 < $id) {
			$afterRel = $this->categoryRel->existing($id, $category_id)->first();
		} else {
			$afterRel = (object)[
				'product_id'  => 0,
				'category_id' => $category_id,
			];
		}

		$list4Move = $this->categoryRel
			->whereCategoryId($afterRel->category_id)
			->whereIn('product_id', $list)
			->orderBy('order')
			->get()->all();

		$lastRel = $afterRel;
		foreach ($list4Move as $rel4Move) {
			$this->init($rel4Move->product_id)->moveAfter($afterRel->category_id, $lastRel->product_id);
			$lastRel = $rel4Move;
		}

		return $this;

	}


	public function changeBatchCategory($products, $categoryFromId, $categoryToId)
	{

		$this->ob2id($products);

		foreach ($products as $product_id) {
			if ($categoryFromId == $categoryToId) {
				continue;
			}
			$this->init($product_id)
				->removeFromCategory($categoryFromId)
				->add2Category($categoryToId);
		}

	}

	/**
	 * @param \Illuminate\Pagination\Paginator $list
	 *
	 * @return array
	 */
	public function list2Array($list)
	{
		$result = [];
		foreach ($list as $item) {
			$result[] = [
				'id'   => $item->id,
				'name' => $item->name,
			];
		}

		return [
			'data' => $result,
			'page' => [
				'page'  => $list->getCurrentPage(),
				'total' => $list->getTotal(),
			]
		];
	}

	/**
	 * @return ProductTag[]
	 */
	public function tagList()
	{
		return $this->tag->get()->all('id', 'name');
	}

	/**
	 * @return ProductType[]
	 */
	public function types()
	{
		return $this->type->get(['id', 'name'])->all();
	}

	public function tagNames()
	{
		$list = [];
		foreach ($this->product->tags as $tag) {
			$list[] = (object)['text' => $tag->name];
		}

		return $list;
	}

	public function setTagNames(array $tagNames)
	{
		$this->tagRel->doReplaceTags($this->product->id, $tagNames);
	}

	public function termTags($term)
	{
		$term = $term . '%';
		$result = $this->tag->orderBy('name')->where('name', 'LIKE', $term)->lists('name');
		$list = [];
		foreach ($result as $value) {
			$list[] = (object)['text' => $value];
		}

		return $list;

	}

	public function getExtras()
	{
		$item = $this->get();
		$extras = [
			'tags'             => $this->tagNames(),
			'category_type_id' => $item->product_type_id,
			'type'             => $item->product_type_id > 0 ? $item->type->name : '',
		];

		return $extras;
	}

	private function onlyCreateInput($item)
	{
		$keys = array_flip($this->createInput);

		return array_intersect_key($item, $keys);
	}

	private function onlyUpdateInput($item)
	{
		$keys = array_flip($this->updateInput);

		return array_intersect_key($item, $keys);
	}

	private function lock()
	{
		$table = $this->product->getTable();
		$this->product->getConnection()->getPdo()->exec("LOCK TABLES `$table` WRITE");
	}

	private function unlock()
	{
		$this->product->getConnection()->getPdo()->exec("UNLOCK TABLES");
	}

	private function lockRel()
	{
		$table = $this->categoryRel->getTable();
		$this->categoryRel->getConnection()->getPdo()->exec("LOCK TABLES `$table` WRITE");
	}

	private function ob2id(&$list)
	{
		if (!is_array($list)) {
			$list = [$list];
		}
		if (!empty($list[0]) && is_object($list[0])) {
			foreach ($list as &$val) {
				$val = $val->id;
			}
		}
	}

}