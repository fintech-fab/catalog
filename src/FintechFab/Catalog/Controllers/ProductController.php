<?php

namespace FintechFab\Catalog\Controllers;


use App;
use FintechFab\Catalog\Components\ProductSearchComponent;
use Input;
use ProductAdmin;

class ProductController extends \Controller
{

	public function create()
	{
		if (ProductAdmin::create(Input::all())) {
			$cat = ProductAdmin::get();

			return $this->restItem($cat->id);
		}

		return ['errors' => ProductAdmin::errors()];

	}

	public function update($id)
	{
		$cat = ProductAdmin::init($id);
		$cat->setTagNames(Input::get('product_tags'));
		if ($cat->update(Input::all())) {
			return $this->restItem($id);
		}

		return ['errors' => $cat->errors()];
	}


	public function tags()
	{
		return ProductAdmin::tagList();
	}

	public function types()
	{
		return ProductAdmin::types();
	}

	public function enable()
	{
		ProductAdmin::init(Input::get('id'))->toggleEnable();

		return '';
	}

	public function remove()
	{
		ProductAdmin::init(Input::get('id'))->remove();

		return '';
	}

	public function index()
	{
		/** @var ProductSearchComponent $search */
		$search = App::make(ProductSearchComponent::class);
		$result = $search
			->filterCategories(Input::get('categories'))
			->paginate(30);

		return \Response::json(ProductAdmin::list2Array($result));
	}

}