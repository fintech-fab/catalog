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
	 * @param integer|array $categories
	 *
	 * @return ProductComponent
	 */
	public function add2Category($categories = [])
	{

		if (!is_array($categories)) {
			$categories = [$categories];
		}
		if (is_object($categories[0])) {
			foreach ($categories as &$item) {
				$item = $item->id;
			}
		}

		$this->categoryRel->addProduct2Category($this->get()->id, $categories);

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

	public function typeList()
	{
		return $this->type->get(['id', 'name'])->lists('name', 'id');
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
		$this->product->getConnection()->table($this->product->getTable())->lock();
	}

	private function unlock()
	{
		$this->product->getConnection()->table($this->product->getTable())->lock(false);
	}


}