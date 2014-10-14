<?php

namespace FintechFab\Catalog\Controllers;


use CategoryAdmin;
use FintechFab\Catalog\Helpers\Core;
use FintechFab\Catalog\Queue\ParseCategories;
use Input;
use Request;
use View;

class CategoryController extends \Controller
{

	public function index()
	{
		$this->make('index', [], 'index');
	}

	public function template($tpl)
	{
		return \View::make('ff-cat::' . $tpl);
	}


	public function edit($id = null)
	{
		if ($id > 0) {
			CategoryAdmin::init($id);
		}

		return $this->makePartial('edit');
	}

	public function create()
	{
		if (CategoryAdmin::create(Input::all())) {
			$cat = CategoryAdmin::get();

			return $this->restItem($cat->id);
		}

		return ['errors' => CategoryAdmin::errors()];

	}

	public function update($id)
	{
		$cat = CategoryAdmin::init($id);
		$cat->setTagNames(Input::get('category_tags'));
		if ($cat->update(Input::all())) {
			return $this->restItem($id);
		}

		return ['errors' => $cat->errors()];
	}

	public function enable()
	{
		CategoryAdmin::init(Input::get('id'))->toggleEnable();

		return '';
	}

	public function remove()
	{
		CategoryAdmin::init(Input::get('id'))->remove();

		return '';
	}

	public function createSymlink()
	{
		$id = Input::get('id');
		$to = Input::get('to');
		CategoryAdmin::init($id)->symlink($to);

		return '';
	}

	public function moveParent()
	{
		$id = Input::get('id');
		$to = Input::get('to');
		CategoryAdmin::init($id)->move2Parent($to);

		return '';
	}

	public function moveAfter()
	{
		$id = Input::get('id');
		$after = Input::get('after');
		$pid = Input::get('pid');
		CategoryAdmin::init($id)->moveParentAndAfter($after, $pid);

		return CategoryAdmin::get()->toJson();
	}

	public function restTree()
	{
		return \Response::json(CategoryAdmin::treeToArray());
	}

	public function restItem($id)
	{
		return \Response::json([
			'category' => CategoryAdmin::init($id)->get(),
			'extras'   => CategoryAdmin::init($id)->getExtras(),
			'types' => CategoryAdmin::types(),
		]);
	}

	public function autocompleteTags()
	{
		$list = CategoryAdmin::termTags(Input::get('term'));
		Core::logLastQuery();

		return $list;
	}


	public function formCategoryUploadCreate()
	{
		$file = null;
		if (Input::hasFile('file')) {
			$file = Input::file('file');
			if (!$file->isValid()) {
				$file = null;
			}
		}

		if ($file) {
			ParseCategories::createTask($file);
		}

		return \Redirect::route('ff-cat.index');
	}


	protected function setupLayout()
	{
		$this->layout = View::make('ff-cat::layouts.index');
	}

	protected function make($sTemplate, $aParams = array())
	{

		if (Request::ajax()) {
			return $this->makePartial($sTemplate, $aParams);
		} else {
			return $this->layout->nest('content', 'ff-cat::category.' . $sTemplate, $aParams);
		}
	}

	protected function makePartial($sTemplate, $aParams = array())
	{
		return View::make('ff-cat::category.' . $sTemplate, $aParams);
	}

} 