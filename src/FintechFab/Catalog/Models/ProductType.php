<?php


namespace FintechFab\Catalog\Models;

/**
 * @package FintechFab\Catalog\Models
 *
 * @property integer $id
 * @property string  $name
 * @property string  $path
 * @method static ProductType wherePath($path)
 */
class ProductType extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'product_types';

	public $cnt = 0;
} 