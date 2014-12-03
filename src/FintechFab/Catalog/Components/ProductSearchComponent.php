<?php


namespace FintechFab\Catalog\Components;


use FintechFab\Catalog\Models\Product;
use FintechFab\Catalog\Models\ProductCategoryRel;

class ProductSearchComponent
{

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
	public function filterCategories($ids)
	{

		if (!is_array($ids) || 0 == count($ids)) {
			return $this;
		}

		$product = $this->product->getTable();
		$rel = $this->categoryRel->getTable();

		$this->model = $this->m()
			->leftJoin(
				$this->categoryRel->getTable(),
				$rel . '.product_id', '=', $product . '.id'
			)
			->whereIn($rel . '.category_id', $ids);

		return $this;

	}

	/**
	 * @param array $ids
	 *
	 * @return ProductSearchComponent
	 */
	public function filterTypes($ids)
	{
		if (!is_array($ids) || 0 == count($ids)) {
			return $this;
		}

		$this->model = $this->m()->whereIn('product_type_id', $ids);

		return $this;
	}

	/**
	 * @param integer|null $enabled
	 *
	 * @return ProductSearchComponent
	 */
	public function filterEnabled($enabled)
	{
		if (strlen($enabled) === 0) {
			return $this;
		}

		$this->model = $this->m()->whereEnabled($enabled);

		return $this;
	}

	/**
	 * @param integer|null $deleted
	 *
	 * @return ProductSearchComponent
	 */
	public function filterDeleted($deleted)
	{
		if (strlen($deleted) === 0) {
			return $this;
		}

		$this->model = $this->m()->whereDeleted($deleted);

		return $this;
	}

	/**
	 * @param string $field
     * @param string $value
     *
     * @return ProductSearchComponent
     */
    public function searchFieldPart($field, $value)
    {
        if (strlen($value) === 0) {
            return $this;
        }

        $this->model = $this->m()->where($field, 'LIKE', '%' . $value . '%');

        return $this;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return ProductSearchComponent
     */
    public function searchField($field, $value)
    {
        if (strlen($value) === 0) {
            return $this;
        }

        $value = $this->product->getConnection()->getPdo()->quote($value);
        $this->model = $this->m()->where($field, 'LIKE', $value);

        return $this;
    }

    /**
     * @param string $field
	 * @param string $direction
	 *
	 * @return ProductSearchComponent
	 */
	public function sorting($field, $direction)
	{
		if (empty($field)) {
			return $this;
		}
		$direction = ($direction == 'asc') ? 'asc' : 'desc';

		if ($field == 'type') {
			$field = 'product_type_id';
		}

		$this->model = $this->m()->orderBy($field, $direction);

		return $this;
	}

	/**
	 * @param $qnt
	 *
	 * @return \Illuminate\Pagination\Paginator
	 */
	public function paginate($qnt)
	{
		$query = $this->m();
		$this->model = null;

		return $query->paginate($qnt);
	}


	private function m()
	{

		if (!$this->model) {
			$this->model = $this->product->newInstance();
		}

		return $this->model;

	}


}
