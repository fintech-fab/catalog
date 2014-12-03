<?php


namespace FintechFab\Catalog\Commands;

use CategoryAdmin as Cat;
use Illuminate\Console\Command;
use ProductAdmin as Item;
use SleepingOwl\Apist\Apist;

class ParseHabrCommand extends Command
{

	protected $name = 'fintech-fab:habr-parser';
	protected $description = 'Parse habrahabr rubrics into our categories';

	public function fire()
	{
		$ob = new ParseHabrApist();
		//$ob->index($this);

		$list = Cat::get()->whereLevel(1)->lists('id', 'sid');

		foreach ($list as $sid => $id) {
			$ob->parseItems($this, '/hub/' . $sid . '/');
		}

	}

}


class ParseHabrApist extends Apist
{

	protected $baseUrl = 'http://habrahabr.ru/';

	/**
	 * @param Command $command
	 */
	public function index($command)
	{

		$rootList = $this->get('/hubs/', [
			'hubs' => Apist::filter('.hubs_categories .categories li')->each([
				'title' => Apist::filter('a')->text()->trim(),
				'link'  => Apist::filter('a')->attr('href')->trim(" /"),
			])
		]);

		foreach ($rootList['hubs'] as $hub) {

			$link = explode('/', $hub['link']);

			$item = [
				'name' => $hub['title'],
				'sid'  => end($link),
				'path' => end($link),
			];

			if ($item['sid'] == 'hubs') {
				continue;
			}

			$cat = Cat::get()->whereSid($item['sid'])->first();
			if (!$cat) {
				$cat = Cat::create($item)->update($item)->get();
			}
			$cat->enable();

			$command->info('worked root category [' . $cat->name . ']');

			$subList = $this->get($hub['link'], [
				'hubs' => Apist::filter('.hubs_list .hubs .hub')->each([
					'title' => Apist::filter('a')->text()->trim(),
					'link'  => Apist::filter('a')->attr('href')->trim(" /"),
				])
			]);

			foreach ($subList['hubs'] as $subHub) {

				$link = explode('/', $subHub['link']);

				$item = [
					'parent_id' => $cat->id,
					'name'      => $subHub['title'],
					'sid'       => end($link),
					'path'      => end($link),
				];

				$subCat = Cat::get()->whereSid($item['sid'])->whereParentId($cat->id)->first();
				if (!$subCat) {
					$subCat = Cat::create($item)->update($item)->get();
				}
				$subCat->enable();

				$command->info('worked child category [' . $subCat->name . ']');

			}

		}

	}

	/**
	 * @param Command $c
	 * @param string  $url
	 */
	public function parseItems($c, $url)
	{
		$result = $this->get($url, [
			'posts' => Apist::filter('.posts_list .posts .post')->each([
				'title' => Apist::filter('h1 a')->text()->trim(),
				'link'  => Apist::filter('h1 a')->attr('href')->trim(" /"),
				'hubs'  => Apist::filter('.hubs a')->each(Apist::filter('*')->attr('href')->trim(" /")),
			])
		]);

		$c->info('parse url ' . $url);

		foreach ($result['posts'] as $post) {

			$postId = explode('/', $post['link']);
			$postId = end($postId);

			$item = [
				'name' => $post['title'],
				'sid'  => $postId,
				'path' => $postId,
			];

			$product = Item::get()->whereSid($postId)->first();
			if (!$product) {
				$product = Item::create($item)->update($item)->get();
			}
			Item::init($product);
			$product->enable();

			$c->line('worked post [' . $product->name . ']');

			foreach ($post['hubs'] as $hubLink) {
				$hubLink = explode('/', $hubLink);
				$hubSid = end($hubLink);

				$cat = Cat::get()->whereLevel(1)->whereSid($hubSid)->first();
				if ($cat) {
					Item::add2Category($cat);
					$c->line('set to category [' . $cat->name . ']');
				}

			}

		}


		sleep(1);

	}

}