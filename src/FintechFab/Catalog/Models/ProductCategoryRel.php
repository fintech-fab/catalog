<?php


namespace FintechFab\Catalog\Models;

/**
 * Class ProductCategoryRel
 *
 * @property integer $product_id
 * @property integer $category_id
 *
 * @method static ProductCategoryRel existing($pid, $cid)
 * @method static ProductCategoryRel whereCategoryId()
 * @method static ProductCategoryRel whereProductId()
 *
 * @package FintechFab\Catalog\Models
 */
class ProductCategoryRel extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'product_category_rel';
	public $fillable = ['product_id', 'category_id'];
	public $timestamps = false;


	public function addProduct2Category($product_id, $categories)
	{
		foreach ($categories as $category_id) {

			if ($this->existing($product_id, $category_id)->exists()) {
				continue;
			}

			self::create([
				'product_id'  => $product_id,
				'category_id' => $category_id,
			]);

		}
	}

	/**
	 * @param ProductCategoryRel $query
	 * @param integer            $pid
	 * @param integer            $cid
	 *
	 * @return ProductCategoryRel
	 */
	public function scopeExisting($query, $pid, $cid)
	{

		return $query->whereCategoryId($cid)
			->whereProductId($pid);

	}

}
