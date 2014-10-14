<?php


namespace FintechFab\Catalog\Components;


use FintechFab\Catalog\Models\Category;
use FintechFab\Catalog\Models\CategoryTag;
use FintechFab\Catalog\Models\CategoryTagRel;
use FintechFab\Catalog\Models\CategoryType;

class CategoryPublic {


	/**
	 * @var Category
	 */
	private $category;

	/**
	 * @var CategoryType
	 */
	private $type;

	/**
	 * @var CategoryTag
	 */
	private $tag;

	/**
	 * @var CategoryTagRel
	 */
	private $tagRel;


	public function __construct(
		Category $category,
		CategoryType $type,
		CategoryTag $tag,
		CategoryTagRel $tagRel
	)
	{
		$this->category = $category;
		$this->type = $type;
		$this->tag = $tag;
		$this->tagRel = $tagRel;
	}

	/**
	 * @return \FintechFab\Catalog\Models\Category
	 */
	public function get()
	{
		return $this->category;
	}





} 