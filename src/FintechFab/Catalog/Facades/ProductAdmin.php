<?php

namespace FintechFab\Catalog\Facades;

use Illuminate\Support\Facades\Facade;

class ProductAdmin extends Facade
{

	protected static function getFacadeAccessor()
	{
		return 'ff.product.admin';
	}

}