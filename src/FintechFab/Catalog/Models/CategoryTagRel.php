<?php


namespace FintechFab\Catalog\Models;

/**
 * Class CategoryTagRel
 *
 * @package FintechFab\Catalog\Models
 *
 * @method static categoryTagRel whereCategoryId($id)
 */
class CategoryTagRel extends \Eloquent
{

	public $connection = 'ff-cat';
	public $table = 'category_tag_rel';
	public $fillable = ['category_tag_id', 'category_id'];
	public $timestamps = false;

	public function tag()
	{
		return $this->hasOne(CategoryTag::class, 'category_tag_id', 'id');
	}

	public function doReplaceTags($id, $tagNames)
	{

		$this->newInstance()->whereCategoryId($id)->delete();

		foreach ($tagNames as $name) {
			if (is_array($name) && isset($name['text'])) {
				$name = $name['text'];
			}
			$tag = CategoryTag::add($name);
			if ($tag) {
				CategoryTagRel::create([
					'category_id'     => $id,
					'category_tag_id' => $tag->id,
				]);
			}
		}

	}

} 