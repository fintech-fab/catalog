<?php


namespace FintechFab\Catalog\Models;

/**
 * Class Category
 *
 * @package FintechFab\Catalog\Models
 *
 * @property integer             $id
 * @property integer             $level
 * @property integer             $order
 * @property integer             $parent_id
 * @property string              $path
 * @property string              $name
 * @property integer             $enabled
 * @property integer             $deleted
 * @property integer             $category_type_id
 * @property integer             $symlink_id
 * @property integer             $left
 * @property integer             $right
 * @property string              $sid
 * @property string              $code
 *
 * @property Category            $symlink
 * @property Category            $parent
 * @property CategoryType        $type
 * @property Category[]          $descendants
 * @property CategoryTag[]       $tags
 *
 * @method static Category whereParentId($parent_id)
 * @method static Category whereSymlinkId($symlink_id)
 * @method static Category find($id)
 * @method static Category first()
 * @method static Category whereCode($code)
 * @method Category newInstance()
 */
class Category extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'categories';

	public $fillable = ['name', 'path', 'level', 'order', 'parent_id', 'code'];

	public function type()
	{
		return $this->belongsTo(CategoryType::class, 'category_type_id', 'id');
	}


	public function tags()
	{
		return $this->belongsToMany(CategoryTag::class, 'category_tag_rel');
	}

	public function parent()
	{
		return $this->hasOne(self::class, 'id', 'parent_id');
	}

	public function symlink()
	{
		return $this->hasOne(self::class, 'id', 'symlink_id');
	}

	public function descendants()
	{
		/** @noinspection PhpUndefinedMethodInspection */
		return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('order');
	}

	public function resetLevelCascade()
	{
		foreach ($this->descendants as $cat) {
			$cat->level = $this->level + 1;
			$cat->save();
			$cat->resetLevelCascade();
		}
	}

	public function toggleEnabled()
	{
		$this->enabled = $this->enabled
			? 0
			: 1;
		$this->save();
	}

	public function remove()
	{
		$this->deleted = 1;
		$this->save();
	}

	public function setSymlink($id)
	{
		$this->symlink_id = $id;
		$this->save();
	}

	public function sysName()
	{
		return '[' . $this->id . '] ' . e($this->name);
	}

}