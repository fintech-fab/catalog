<?php


namespace FintechFab\Catalog\Models;

use FintechFab\Catalog\Helpers\Core;

/**
 * Class CategoryTag
 *
 * @package FintechFab\Catalog\Models
 *
 * @property integer $id
 * @property string  $name
 * @property string  $path
 *
 * @method static CategoryTag whereName($name)
 * @method static CategoryTag[] findMany($ids)
 * @method static CategoryTag wherePath($path)
 */
class CategoryTag extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'category_tags';
	public $fillable = ['name', 'path'];
	public $timestamps = false;

	public $cnt = 0;

	/**
	 * @param $name
	 *
	 * @return CategoryTag|null
	 */
	public static function add($name)
	{

		$name = trim($name);
		if (empty($name)) {
			return null;
		}

		$tag = self::whereName($name)->first();
		if ($tag) {
			return $tag;
		}

		$path = Core::translit4url($name);

		$tag = self::create([
			'name' => $name,
			'path' => $path,
		]);

		return $tag;

	}

} 