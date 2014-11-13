<?php


namespace FintechFab\Catalog\Components;


use FintechFab\Catalog\Models\Product;
use FintechFab\Catalog\Models\ProductCategoryRel;

class ProductSearchComponent {

	/**
	 * @var Product
	 */
	private $product;

	/**
	 * @var Product
	 */
	private $model;

	/**
	 * @var ProductCategoryRel
	 */
	private $categoryRel;

	public function __construct(
		Product $product,
		ProductCategoryRel $categoryRel
	)
	{
		$this->product = $product;
		$this->categoryRel = $categoryRel;
	}

	/**
	 * @param array $ids
	 *
	 * @return ProductSearchComponent
	 */
	public function filterCategories($ids){

		if(!is_array($ids) || 0 == count($ids)){
			return $this;
		}

		$product = $this->product->getTable();
		$rel = $this->categoryRel->getTable();

		$this->model = $this->m()
			->leftJoin(
				$this->categoryRel->getTable(),
				$rel.'.product_id', '=', $product.'.id'
			)
			->whereIn($rel.'.category_id', $ids);

		return $this;

	}

	/**
	 * @param $qnt
	 *
	 * @return \Illuminate\Pagination\Paginator
	 */
	public function paginate($qnt){
		$query = $this->m();
		$this->model = null;
		return $query->paginate($qnt);
	}


	private function m(){

		if(!$this->model){
			$this->model = $this->product->newInstance();
		}
		return $this->model;

	}


}
