<?php


namespace FintechFab\Catalog\Models;

/**
 * Class Product
 *
 * @property integer      $id
 * @property string       $name
 * @property integer      $enabled
 * @property integer      $deleted
 * @property integer      $product_type_id
 * @property ProductType  $type
 * @property ProductTag[] $tags
 * @property Category[]   $categories
 *
 * @method static Product whereCategoryId($id)
 * @method static Product whereSid($sid)
 * @method static Product whereEnabled($int)
 * @method static Product whereDeleted($int)
 * @method static Product first()
 * @package FintechFab\Catalog\Models
 */
class Product extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'products';
	public $fillable = ['name'];


	public function type()
	{
		return $this->hasOne(ProductType::class, 'id', 'product_type_id');
	}

	public function toggleEnabled()
	{
		$this->enabled = $this->enabled
			? 0
			: 1;
		$this->save();
	}

	public function enable()
	{
		$this->enabled = 1;
		$this->save();
	}

	public function remove()
	{
		$this->deleted = 1;
		$this->save();
	}

	public function categories()
	{
		return $this->belongsToMany(Category::class, 'product_category_rel');
	}


} 