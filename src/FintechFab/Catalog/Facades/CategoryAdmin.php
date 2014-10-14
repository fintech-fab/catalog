<?php

namespace FintechFab\Catalog\Facades;

use Illuminate\Support\Facades\Facade;

class CategoryAdmin extends Facade {

	protected static function getFacadeAccessor() { return 'ff.category.admin'; }

}