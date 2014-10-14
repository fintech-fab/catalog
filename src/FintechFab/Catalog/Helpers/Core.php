<?php


namespace FintechFab\Catalog\Helpers;


use App;
use DB;
use FintechFab\Catalog\Components\CategoryComponent;
use Log;

class Core
{

	/**
	 * @return CategoryComponent
	 */
	public static function category()
	{
		return App::make(CategoryComponent::class);
	}

	public static function translit($value)
	{
		$rus = array('ё', 'ж', 'ц', 'ч', 'ш', 'щ', 'ю', 'я', 'Ё', 'Ж', 'Ц', 'Ч', 'Ш', 'Щ', 'Ю', 'Я');
		$lat = array('yo', 'zh', 'tc', 'ch', 'sh', 'sh', 'yu', 'ya', 'YO', 'ZH', 'TC', 'CH', 'SH', 'SH', 'YU', 'YA');
		$string = str_replace($rus, $lat, $value);

		$rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ъ', 'Ы', 'Ь', 'Э', 'а', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ъ', 'ы', 'ь', 'э');
		$lat = array('A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', '', 'I', '', 'E', 'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', '', 'i', 'e');
		$string = str_replace($rus, $lat, $string);

		$string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

		return $string;

	}

	public static function translit4url($value)
	{
		$value = self::translit($value);
		$value = strtolower($value);

		$value = preg_replace('/[^A-Za-z0-9]+/', '-', $value);
		$value = trim($value, '-');

		return $value;
	}

	public static function logLastQuery()
	{
		$list = DB::connection('ff-cat')->getQueryLog();
		Log::info('query', [end($list)]);
	}

} 