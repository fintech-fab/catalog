<?php namespace FintechFab\Catalog;

use App;
use FintechFab\Catalog\Components\CategoryComponent;
use FintechFab\Catalog\Components\CategorySiteComponent;
use FintechFab\Catalog\Controllers\CategoryController;
use FintechFab\Catalog\Exceptions\CategoryException;
use Illuminate\Support\ServiceProvider;
use Route;
use View;

class CatalogServiceProvider extends ServiceProvider
{

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('fintech-fab/catalog', 'ff-cat');
		View::addNamespace('ff-cat', __DIR__ . '/Views');
		View::addExtension('html', 'php');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->routes();
		App::singleton(CategoryComponent::class, CategoryComponent::class);

		App::error(function (CategoryException $exception) {
			$code = $exception->getCode() ? $exception->getCode() : 500;

			return \Response::json(
				[
					'error' => [
						'message' => $exception->getMessage(),
						'code'    => $code,
					]
				],
				$code
			);
		});

		App::bind('ff.category.admin', function () {
			return App::make(CategoryComponent::class);
		});
		App::bind('ff.category.site', function () {
			return App::make(CategorySiteComponent::class);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

	private function routes()
	{
		Route::group(array('prefix' => 'ff-cat',), function () {

			Route::get('/', [
				'as'   => 'ff-cat.index',
				'uses' => CategoryController::class . '@index'
			]);

			Route::get('/category/select/one', [
				'as'   => 'ff.cat.category.select.one',
				'uses' => CategoryController::class . '@selectOne',
			]);

			Route::post('/category/move/parent', [
				'as'   => 'ff.cat.category.move.parent',
				'uses' => CategoryController::class . '@moveParent',
			]);

			Route::post('/category/move/after', [
				'as'   => 'ff.cat.category.move.after',
				'uses' => CategoryController::class . '@moveAfter',
			]);

			Route::post('/category/move/symlink', [
				'as'   => 'ff.cat.category.move.symlink',
				'uses' => CategoryController::class . '@createSymlink',
			]);

			Route::get('/category/tree', [
				'as'   => 'ff.cat.category.tree',
				'uses' => CategoryController::class . '@tree',
			]);

			Route::get('/category/edit/{id?}', [
				'as'   => 'ff.cat.category.edit',
				'uses' => CategoryController::class . '@edit',
			]);

			Route::post('/category/update/{id?}', [
				'as'   => 'ff.cat.category.update',
				'uses' => CategoryController::class . '@update',
			]);

			Route::post('/category/create', [
				'as'   => 'ff.cat.category.create',
				'uses' => CategoryController::class . '@create',
			]);

			Route::post('/category/remove', [
				'as'   => 'ff.cat.category.remove',
				'uses' => CategoryController::class . '@remove',
			]);

			Route::post('/category/enable', [
				'as'   => 'ff.cat.category.enable',
				'uses' => CategoryController::class . '@enable',
			]);

			Route::get('/category/tags/autocomplete', [
				'as'   => 'ff.cat.category.tags.autocomplete',
				'uses' => CategoryController::class . '@autocompleteTags',
			]);

			Route::get('/category/upload', [
				'as'   => 'ff.cat.category.tags.autocomplete',
				'uses' => CategoryController::class . '@autocompleteTags',
			]);

			Route::get('/rest/categories/tree', [
				'as'   => 'ff.cat.rest.category.tree',
				'uses' => CategoryController::class . '@restTree',
			]);

			Route::get('/rest/categories/item/{id}', [
				'as'   => 'ff.cat.rest.category.item',
				'uses' => CategoryController::class . '@restItem',
			]);


			Route::get('/template/{tpl}', [
				'uses' => CategoryController::class . '@template',
			]);

		});

	}

}
