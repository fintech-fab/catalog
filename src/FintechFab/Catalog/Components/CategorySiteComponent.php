<?php


namespace FintechFab\Catalog\Components;


use FintechFab\Catalog\Models\Category;
use FintechFab\Catalog\Models\CategoryTag;
use FintechFab\Catalog\Models\CategoryTagRel;
use FintechFab\Catalog\Models\CategoryType;

class CategorySiteComponent
{


	/**
	 * @var Category
	 */
	private $category;

	/**
	 * @var CategoryType
	 */
	private $type;

	/**
	 * @var CategoryTag
	 */
	private $tag;

	/**
	 * @var CategoryTagRel
	 */
	private $tagRel;

	/**
	 * @var array
	 */
	private $queryLog;


	public function __construct(
		Category $category,
		CategoryType $type,
		CategoryTag $tag,
		CategoryTagRel $tagRel
	)
	{
		$this->category = $category;
		$this->type = $type;
		$this->tag = $tag;
		$this->tagRel = $tagRel;

		$this->category->getConnection()->listen(function ($sql, $bindings) {
			foreach ($bindings as $val) {
				$sql = preg_replace('/\?/', "'{$val}'", $sql, 1);
			}
			$this->queryLog[] = $sql;
		});

	}

	/**
	 * @return \FintechFab\Catalog\Models\Category
	 */
	public function get()
	{
		return $this->category;
	}


	/**
	 * @param \FintechFab\Catalog\Models\Category|integer $category
	 *
	 * @return CategorySiteComponent
	 */
	public function init($category)
	{
		if ($category === 0) {
			$this->category = $this->category->newInstance();
		} elseif (!is_object($category)) {
			$this->category = $this->category->newInstance()->find($category);
		} else {
			$this->category = $category;
		}

		return $this;
	}

	/**
	 * @param array $with
	 *
	 * @return array
	 */
	public function treeList($with = [])
	{

		/**
		 * @var Category $range
		 */

		$list = $this->category
			->orderLeft()
			->notDeleted()
			->enabled();

		$list = $this->mergeWith($list, $with);
		$list = $list->get()->all();

		$this->clearTreeList($list);

		return $list;
	}

	/**
	 * @return array
	 */
	public function treeByItem()
	{

		/**
		 * @var Category $range
		 * @var Category[] $list
		 */

		$category = $this->get();

		$list = $this->category
			->orderLeft()
			->parentMargin($category)
			->notDeleted()
			->enabled()
			->get()
			->all();

		$this->clearTreeList($list, $category);

		return $list;
	}

	/**
	 * @param $path
	 *
	 * @return \FintechFab\Catalog\Models\CategoryTag
	 */
	public function tagByPath($path)
	{
		return $this->tag->wherePath($path)->first();
	}

	/**
	 * @param string $path
	 *
	 * @return \FintechFab\Catalog\Models\CategoryType
	 */
	public function typeByPath($path)
	{
		return $this->type->wherePath($path)->first();
	}

	/**
	 * @return \FintechFab\Catalog\Models\CategoryType[]
	 */
	public function typeList()
	{
		$types = $this->type->all();

		$quantity = $this->category
			->notDeleted()
			->enabled()
			->selectRaw('category_type_id, count(category_type_id) as cnt')
			->groupBy('category_type_id')
			->get()
			->lists('cnt', 'category_type_id');

		foreach ($types as $type) {
			$type->cnt = isset($quantity[$type->id])
				? $quantity[$type->id]
				: 0;
		}

		return $types;

	}

	/**
	 * @param \FintechFab\Catalog\Models\CategoryTag[]|\FintechFab\Catalog\Models\CategoryTag|integer[]|integer $tags
	 * @param array                                                                                             $with
	 *
	 * @return array
	 */
	public function treeByTag($tags, $with = [])
	{
		/**
		 * @var Category $range
		 */

		$list = $this->category
			->orderLeft()
			->notDeleted()
			->enabled()
			->tagged($tags);
		$this->mergeWith($list, $with);
		$list = $list->get()->all();

		return $list;
	}

	/**
	 * @param \FintechFab\Catalog\Models\CategoryType[]|\FintechFab\Catalog\Models\CategoryType|integer[]|integer $types
	 * @param array                                                                                               $with
	 *
	 * @return array
	 */
	public function treeByType($types, $with = [])
	{

		/**
		 * @var Category $range
		 */

		$list = $this->category
			->orderLeft()
			->notDeleted()
			->enabled()
			->typed($types);
		$this->mergeWith($list, $with);
		$list = $list->get()->all();

		return $list;
	}

	/**
	 * @param array $list
	 * @param \FintechFab\Catalog\Models\Category $node
	 */
	private function clearTreeList(&$list, $node = null)
	{

		$prev = [
			0 => [
				'id'        => 0,
				'parent_id' => 0,
				'level'     => 0,
			]
		];

		if ($node) {
			$prev = [
				$node->level => $node
			];
		}

		foreach ($list as $key => $item) {

			// not root: parent lost
			if ($item->level > 0 && !isset($prev[$item->level - 1])) {
				unset($list[$key]);
				continue;
			}

			if (isset($prev[$item->level - 1])) {

				// not equals parent id
				if ($item->parent_id != $prev[$item->level - 1]->id) {
					unset($list[$key]);
					continue;
				}

			}

			$prev[$item->level] = $item;

		}

	}

	/**
	 * @return array
	 */
	public function treeNest()
	{

	}

	/**
	 * @param array $with
	 *
	 * @return \FintechFab\Catalog\Models\Category[]
	 */
	public function parents($with = [])
	{

		$left = $this->get()->left;
		$right = $this->get()->right;

		$list = $this->category
			->where('left', '<=', $left)
			->where('right', '>=', $right)
			->orderLeft();

		return $this->mergeWith($list, $with)->get()->all();

	}

	/**
	 * @param array $with
	 *
	 * @return \FintechFab\Catalog\Models\Category[]
	 */
	public function neighbors($with = [])
	{
		$category = $this->get();
		if (!$category->id) {
			$level = 0;
			$parent_id = 0;
		} else {
			$level = $category->level;
			$parent_id = $category->parent_id;
		}

		$list = $this->category
			->whereLevel($level)
			->whereParentId($parent_id)
			->notDeleted()
			->enabled()
			->orderLeft();

		return $this->mergeWith($list, $with)->get()->all();

	}


	/**
	 * @return \FintechFab\Catalog\Models\CategoryTag[]
	 */
	public function tagList()
	{
		$relations = $this->tagRel
			->selectRaw('category_tag_id, count(category_tag_id) as cnt')
			->groupBy('category_tag_id')
			->get()
			->lists('cnt', 'category_tag_id');

		$ids = array_keys($relations);

		$tags = $this->tag->findMany($ids);

		foreach ($tags as $tag) {
			$tag->cnt = $relations[$tag->id];
		}

		return $tags;

	}

	/**
	 * @return array
	 */
	public function queryLog()
	{
		return $this->queryLog;
	}

	/**
	 * @param string $str
	 */
	public function queryLogDelimiter($str)
	{
		$this->queryLog[] = $str;
	}


	/**
	 * @param \Eloquent $list
	 * @param array     $with
	 *
	 * @return \Eloquent
	 */
	private function mergeWith($list, $with)
	{
		if (empty($with)) {
			return $list;
		}

		if (!is_array($with)) {
			$with = [$with];
		}

		foreach ($with as $withName) {
			$list->with($withName);
		}

		return $list;

	}

} 