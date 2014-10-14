<?php


namespace FintechFab\Catalog\Models;

/**
 * Class CategoryType
 *
 * @package FintechFab\Catalog\Models
 *
 * @property string $name
 */
class CategoryType extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'category_types';

} 