<?php


namespace FintechFab\Catalog\Components;

use FintechFab\Catalog\Exceptions\CategoryException;
use FintechFab\Catalog\Helpers\Core;
use FintechFab\Catalog\Models\Category;
use FintechFab\Catalog\Models\CategoryTag;
use FintechFab\Catalog\Models\CategoryTagRel;
use FintechFab\Catalog\Models\CategoryType;
use Illuminate\Support\MessageBag;
use Log;

class CategoryComponent
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
	 * @var \Illuminate\Validation\Validator
	 */
	private $validator;

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
	}

	/**
	 * @param array $item
	 *
	 * @return CategoryComponent|false
	 */
	public function create(array $item)
	{
		$this->prepareRequiredData($item);
		$item['level'] = $this->calculateLevel($item['parent_id']);
		$item['order'] = $this->calculateOrder($item['parent_id']);
		$cat = $this->category->newInstance($item);
		$this->init($cat);

		if (!$this->validate($item)) {
			return false;
		}

		$cat->save();
		$this->recalculateMargins();

		$this->category = $cat->find($cat->id);

		return $this;

	}

	public function update(array $item)
	{

		$this->guardUpdateData($item);
		foreach ($item as $key => $value) {
			$this->category->$key = $value;
			$this->category->setAttribute($key, $value);
		}

		if (!$this->validate($item)) {
			return false;
		}

		$this->category->save();

		return $this;
	}

	/**
	 * @return \FintechFab\Catalog\Models\Category
	 */
	public function get()
	{
		return $this->category;
	}


	private function prepareRequiredData(array &$item)
	{

		$item['parent_id'] = empty($item['parent_id'])
			? 0
			: (int)$item['parent_id'];

		if ($item['parent_id'] <= 0) {
			$item['parent_id'] = 0;
		}

		$item['path'] = empty($item['path'])
			? ''
			: trim($item['path']);

		if (empty($item['path']) && !empty($item['name'])) {
			$item['path'] = Core::translit4url($item['name']);
		}

	}

	private function calculateLevel($parent_id)
	{

		if ($parent_id <= 0) {
			return 0;
		}

		/** @var Category $parent */
		$parent = $this->category->newInstance()->find($parent_id);

		return $parent->level + 1;

	}

	private function calculateOrder($parent_id)
	{

		/** @var Category $cat */
		$cat = $this->category->newInstance();
		$max = $cat->whereParentId($parent_id)->max('order');
		if (!$max) {
			$max = 0;
		}

		return $max + 1;

	}

	/**
	 * @param \FintechFab\Catalog\Models\Category|integer $category
	 *
	 * @return CategoryComponent
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
	 * @param integer $after_id
	 *
	 * @return CategoryComponent
	 * @throws \FintechFab\Catalog\Exceptions\CategoryException
	 */
	public function moveAfter($after_id)
	{
		$after = $this->category->find($after_id);
		if (!$after) {
			$after = (object)array(
				'id'    => 0,
				'order' => 0,
			);
		}
		$current = $this->category;
		if ($after->id && $after->parent_id != $current->parent_id) {
			throw new CategoryException('unable move category order in different nodes');
		}

		Log::info('move category', [
			'current' => [
				'id'    => $current->id,
				'order' => $current->order,
			],
			'after'   => [
				'id'    => $after->id,
				'order' => $after->order,
			],
		]);

		$query = $current->getConnection()
			->table($current->table)
			->where('parent_id', $current->parent_id);

		if ($current->order > $after->order) {
			$query
				->where('order', '>', $after->order)
				->where('order', '<', $current->order)
				->update(array(
					'order' => $current->getConnection()->raw('`order`+1')
				));
		} else {
			$query
				->where('order', '>', $current->order)
				->where('order', '<=', $after->order)
				->update(array(
					'order' => $current->getConnection()->raw('`order`-1')
				));
		}

		if ($after->id > 0) {
			$after = $this->category->find($after_id);
		}
		$current->order = $after->order + 1;
		$current->save();
		$this->recalculateMargins();

		return $this;
	}

	/**
	 * get id for change parent before move after
	 *
	 * @param integer $after_id
	 * @param integer $parent_id
	 *
	 * @return CategoryComponent
	 */
	public function moveParentAndAfter($after_id, $parent_id = null)
	{

		$current = $this->category;
		$after = $this->category->find($after_id);
		if (!$after && $after_id == 0) {
			$after = (object)array(
				'id'        => 0,
				'parent_id' => 0,
			);
		}
		$parent_id = ($parent_id > 0) ? $parent_id : $after->parent_id;

		// parent not change (move to first position)
		if ($after->id == $parent_id) {
			return $this->moveAfter(0);
		}

		// parent not change (move to after id position)
		if ($after->parent_id == $current->parent_id && $current->parent_id > 0) {
			return $this->moveAfter($after->id);
		}

		// parent change before moving (move to after id position)
		$res = $this->move2Parent($parent_id)->moveAfter($after->id);

		return $res;

	}

	/**
	 * @return CategoryComponent
	 */
	public function extractFromParent()
	{
		$current = $this->category;

		if ($current->parent_id == 0) {
			return $this;
		}

		$current->getConnection()
			->table($current->table)
			->where('parent_id', $current->parent_id)
			->where('order', '>', $current->order)
			->update(array(
				'order' => $current->getConnection()->raw('`order`-1')
			));

		$current->parent_id = 0;
		$current->save();
		$this->recalculateMargins();

		return $this;

	}

	/**
	 * @param integer $id
	 *
	 * @throws \FintechFab\Catalog\Exceptions\CategoryException
	 * @return CategoryComponent
	 */
	public function move2Parent($id)
	{
		if ($this->findDescendant($id, $this->category->id)) {
			throw new CategoryException('tree conflict');
		}

		if ($id > 0) {
			$parent = $this->category->find($id);
			if ($parent->symlink_id) {
				throw new CategoryException('Category [' . $parent->id . '] is symlink!');
			}
			if ($this->category->symlink_id) {
				while ($parent) {
					if ($parent->id == $this->category->symlink_id) {
						throw new CategoryException('Symlink category [' . $this->category->id . '] unable move to symlink id parent [' . $parent->id . ']');
					}
					$parent = $this->category->find($parent->parent_id);
				}
			}
		}

		$this->extractFromParent();

		$this->category->parent_id = $id;
		$this->category->level = $this->calculateLevel($id);
		$this->category->order = $this->calculateOrder($id);
		$this->category->save();
		$this->category->resetLevelCascade();
		$this->recalculateMargins();

		return $this;
	}

	private function findDescendant($id, $parent_id)
	{

		if ($id == $parent_id) {
			return true;
		}

		$category = $this->category->find($parent_id);
		$descendants = $category->descendants;
		foreach ($descendants as $item) {
			if ($item->id == $id) {
				return true;
			}
			if ($this->findDescendant($id, $item->id)) {
				return true;
			}
		}

		return false;

	}

	private function validate(array &$item)
	{

		$this->clearData($item);
		$this->createSymlinkValidator($item);

		$rules = [
			'name'       => 'required',
			'symlink_id' => 'symlink',
			'parent_id'  => 'symlink',
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

	/**
	 * @return Category[]
	 */
	public function topList()
	{
		return $this->category->whereParentId(0)->orderBy('order')->get()->all();
	}

	private function clearData(array &$item)
	{
		$clear = [
			'_token',
			'category_tags',
		];

		foreach ($clear as $key) {
			if (isset($item[$key])) {
				unset($item[$key]);
			}
		}

	}

	private function guardUpdateData(array &$item)
	{
		$this->clearData($item);

		$guard = [
			'parent_id',
			'level',
			'order',
			'enabled',
			'deleted',
		];

		foreach ($guard as $key) {
			if (isset($item[$key])) {
				unset($item[$key]);
			}
		}

	}

	public function toggleEnable()
	{
		$this->category->toggleEnabled();
	}

	public function remove()
	{
		$this->category->remove();
	}

	public function typeList()
	{
		return $this->type->get(['id', 'name'])->lists('name', 'id');
	}

	/**
	 * @return CategoryType[]
	 */
	public function types()
	{
		return $this->type->get(['id', 'name'])->all();
	}

	public function recalculateMargins()
	{

		$this->category->getConnection()->table($this->category->table)->lock();
		$i = 0;
		foreach ($this->topList() as $cat) {
			$this->_recalculateMargins($cat, $i);
		}
		$this->category->getConnection()->table($this->category->table)->lock(false);

	}

	private function _recalculateMargins(Category $category, &$i)
	{
		$i++;
		$category->left = $i;
		foreach ($category->descendants as $subcategory) {
			$this->_recalculateMargins($subcategory, $i);
		}
		$i++;
		$category->right = $i;
		$category->save();
	}

	public function symlink($id)
	{

		$this->createSymlinkValidator([]);

		$this->validator = \Validator::make(
			['symlink_id' => $id],
			['symlink_id' => 'symlink']
		);

		if (!$this->validator->fails()) {
			$this->category->setSymlink($id);
		} else {
			throw new CategoryException('unable create symlink');
		}

	}

	private function createSymlinkValidator($item)
	{

		$current = clone $this->category;
		\Validator::extend('symlink', function ($attribute, $value) use ($item, $current) {

			// fail if parent is symlink
			if ($attribute == 'parent_id') {
				if ($value > 0) {
					$parent = $this->category->find($value);
					if ($parent && $parent->symlink_id > 0) {
						return false;
					}
				}

				return true;
			}

			// fail field for this rule
			if ($attribute != 'symlink_id') {
				return false;
			}

			// no symlink
			if ((int)$value <= 0) {
				return true;
			}

			// symlink not exists or deleted
			$sym = $current->find($value);
			if (!$sym || $sym->deleted) {
				return false;
			}

			// current category is symlink exists target
			$target = $current->whereSymlinkId($current->id)->exists();
			if ($target) {
				return false;
			}

			// symlink is symlink!
			if ($sym->symlink_id > 0) {
				return false;
			}

			// new category
			if (!$current->id) {
				$current = $current->newInstance();
				$current->setRawAttributes($item);
			}

			// disable self
			if ($value == $current->id) {
				return false;
			}

			// impossible symlink, if child
			if (0 < count($current->descendants)) {
				return false;
			}

			// no parents, enable any symlink
			if ($current->parent_id == 0) {
				return true;
			}

			// disable symlink to any parent chain
			$parent = $current->parent;
			while ($parent) {
				if ($value == $parent->id) {
					return false;
				}
				$parent = $parent->parent;
			}

			// enable other case
			return true;

		});

	}

	public function tagNames()
	{
		$list = [];
		foreach ($this->category->tags as $tag) {
			$list[] = (object)['text' => $tag->name];
		}

		return $list;
	}

	public function setTagNames(array $tagNames)
	{
		$this->tagRel->doReplaceTags($this->category->id, $tagNames);
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

	public function treeToArray($list = [])
	{
		if (empty($list)) {
			$list = $this->topList();
		}
		$result = [];
		foreach ($list as $cat) {
			$item = [
				'id'               => $cat->id,
				'title'            => $cat->name,
				'name'             => $cat->name,
				'enabled'          => (bool)$cat->enabled,
				'deleted'          => (bool)$cat->deleted,
				'parent_id'        => $cat->parent_id,
				'symlink_id'       => $cat->symlink_id,
				'symlink'          => $cat->symlink_id > 0 ? $cat->symlink->name : '',
				'category_type_id' => $cat->category_type_id,
				'type'             => $cat->category_type_id > 0 ? $cat->type->name : '',
			];
			$item['nodes'] = $this->treeToArray($cat->descendants);
			$result[] = $item;
		}

		return $result;
	}

	public function getExtras()
	{
		$item = $this->get();
		$extras = [
			'symlink'          => $item->symlink_id ? $item->symlink->name : '',
			'tags'             => $this->tagNames(),
			'category_type_id' => $item->category_type_id,
			'type'             => $item->category_type_id > 0 ? $item->type->name : '',
		];

		return $extras;
	}

}