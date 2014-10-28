<?php


namespace FintechFab\Catalog\Models;

/**
 * Class ProductCategoryRel
 *
 * @property integer $product_id
 * @property integer $category_id
 * @property integer $order
 *
 * @method static ProductCategoryRel existing($pid, $cid)
 * @method static ProductCategoryRel whereCategoryId()
 * @method static ProductCategoryRel whereProductId()
 * @method static ProductCategoryRel first()
 *
 * @package FintechFab\Catalog\Models
 */
class ProductCategoryRel extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'product_category_rel';
	public $fillable = ['product_id', 'category_id', 'order'];
	public $timestamps = false;

	/**
	 * add relation product category
	 *
	 * @param integer $product_id
	 * @param array   $categories
	 */
	public function addProduct2Category($product_id, $categories)
	{
		foreach ($categories as $category_id) {

			if ($this->existing($product_id, $category_id)->exists()) {
				continue;
			}

			$max = $this->maxCategoryOrder($category_id);

			self::create([
				'product_id'  => $product_id,
				'category_id' => $category_id,
				'order' => $max + 1,
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

	/**
	 * max value of order field into one category
	 *
	 * @param integer $category_id
	 *
	 * @return integer
	 */
	public function maxCategoryOrder($category_id)
	{
		return (int)self::whereCategoryId($category_id)->max('order');
	}

	/**
	 * set new order value
	 *
	 * @param integer $orderValue
	 */
	public function setOrder($orderValue)
	{
		$this->existing($this->product_id, $this->category_id)->update([
			'order' => $orderValue,
		]);
	}

}
