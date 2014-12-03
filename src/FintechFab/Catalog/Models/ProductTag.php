<?php


namespace FintechFab\Catalog\Models;

use FintechFab\Catalog\Helpers\Core;

/**
 * Class ProductTag
 *
 * @package FintechFab\Catalog\Models
 *
 * @property integer $id
 * @property string  $name
 * @property string  $path
 *
 * @method static ProductTag whereName($name)
 * @method static ProductTag[] findMany($ids)
 * @method static ProductTag wherePath($path)
 */
class ProductTag extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'product_tags';
	public $fillable = ['name', 'path'];
	public $timestamps = false;

	public $cnt = 0;

	/**
	 * @param $name
	 *
	 * @return ProductTag|null
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