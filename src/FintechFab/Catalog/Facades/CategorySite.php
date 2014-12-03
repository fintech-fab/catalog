<?php

namespace FintechFab\Catalog\Facades;

use Illuminate\Support\Facades\Facade;

class CategorySite extends Facade
{

	protected static function getFacadeAccessor()
	{
		return 'ff.category.site';
	}

}