<?php


namespace FintechFab\Catalog\Models;

/**
 * Class CategoryType
 *
 * @package FintechFab\Catalog\Models
 *
 * @property integer $id
 * @property string $name
 * @property string $path
 * @method static CategoryType wherePath($path)
 */
class CategoryType extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'category_types';

	public $cnt = 0;
} 