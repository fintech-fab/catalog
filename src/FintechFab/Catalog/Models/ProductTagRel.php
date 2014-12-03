<?php


namespace FintechFab\Catalog\Models;

/**
 * Class ProductTagRel
 *
 * @package FintechFab\Catalog\Models
 *
 * @method static ProductTagRel whereProductId($id)
 */
class ProductTagRel extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'product_tag_rel';
	public $fillable = ['product_tag_id', 'product_id'];
	public $timestamps = false;

	public function tag()
	{
		return $this->hasOne(ProductTag::class, 'product_tag_id', 'id');
	}

	public function doReplaceTags($id, $tagNames)
	{

		$this->newInstance()->whereProductId($id)->delete();

		foreach ($tagNames as $name) {
			if (is_array($name) && isset($name['text'])) {
				$name = $name['text'];
			}
			$tag = ProductTag::add($name);
			if ($tag) {
				ProductTagRel::create([
					'product_id'     => $id,
					'product_tag_id' => $tag->id,
				]);
			}
		}

	}

} 