<?php


namespace FintechFab\Catalog\Queue;


use CategoryAdmin;
use File;
use FintechFab\Catalog\Exceptions\CategoryException;
use FintechFab\Catalog\Helpers\Core;
use FintechFab\Catalog\Models\Category;
use FintechFab\QueueTasks\Models\QueueTask;
use Monolog\Logger;
use PHPExcel_Cell;
use PHPExcel_Reader_Excel2007;
use PHPExcel_Worksheet_Row;
use PHPExcel_Worksheet_RowIterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ParseCategories
{

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var QueueTask
	 */
	private $task;

	/**
	 * @var PHPExcel_Reader_Excel2007
	 */
	private $excel;

	/**
	 * @var PHPExcel_Worksheet_RowIterator
	 */
	private $rows;

	public static function createTask(UploadedFile $file)
	{

		$task = self::getExistTask();
		if ($task) {
			throw new CategoryException('Task already (#' . $task->id . ')');
		}

		$name = Core::translit4url($file->getClientOriginalName()) . '.' . $file->getClientOriginalExtension();
		$name = date('Ymd-His-') . $name;
		$dir = storage_path() . '/category/upload';
		$path = $file->move($dir, $name)->getRealPath();


		$data = [
			'path' => $path,
		];

		QueueTask::add(ParseCategories::class, $data, [
			'name'               => 'Parse categories from file',
			'sid'                => 'category.parse',
			'exception_attempts' => 2,
		]);

	}

	/**
	 * return not finished category task
	 *
	 * @return QueueTask
	 */
	public static function getExistTask()
	{
		return QueueTask::findNotFinished('category.parse');
	}

	public function fire(QueueTask $task)
	{

		$this->task = $task;

		if (!$this->checkFile()) {
			return;
		}

		$this->openExcel();
		$this->parse();

		$this->openExcel();

		$this->removeFile();
		$task->finish();

	}

	private function checkFile()
	{
		$this->path = $this->task->data('path');

		if (!File::exists($this->path) || !File::isFile($this->path)) {
			$this->task->log(Logger::ERROR, 'File not found (path:' . $this->path . ')');
			$this->task->error();

			return false;
		}

		$this->task->log(Logger::INFO, 'File found (path:' . $this->path . ')');

		return true;

	}

	private function removeFile()
	{
		$removed = File::delete($this->path);
		if (!$removed) {
			$this->task->log(Logger::ERROR, 'Error remove file (path:' . $this->path . ')');

			return;
		}

		$this->task->log(Logger::INFO, 'File has been deleted (path:' . $this->path . ')');

	}

	private function openExcel()
	{
		$this->excel = new PHPExcel_Reader_Excel2007();
		$excel = $this->excel->load($this->path);
		$sheet = $excel->getActiveSheet();
		$this->rows = $sheet->getRowIterator();
	}

	private function parse()
	{

		/**
		 * @var  PHPExcel_Worksheet_Row $row
		 * @var  PHPExcel_Cell          $cell
		 */

		foreach ($this->rows as $row) {

			$this->task->addWorkingQnt(1);
			$cells = $row->getCellIterator();

			foreach ($cells as $cellPos => $cell) {
				$value = $cell->getValue();
				$value = trim($value);
				$this->parseValue(
					$cellPos, $value
				);
			}

		}

	}

	private function parseValue($cellPos, $value)
	{

		/**
		 * @var string   $code
		 * @var Category $current
		 * @var Category $parent
		 */

		static $code = null;
		static $current = null;
		static $parent = null;

		switch ($cellPos) {

			case '0':

				// clear old data
				$code = null;
				$current = null;
				$parent = null;

				// find target category
				if ($value) {
					$current = CategoryAdmin::get()->whereCode($value)->first();
					$code = $value;
				}

				break;

			case '1':

				// find parent category
				if ($value) {
					$parent = CategoryAdmin::get()->whereCode($value)->first();
				}

				break;

			case '2':

				// create new category
				if (!$current) {

					$current = CategoryAdmin::create([
						'name'      => $value,
						'parent_id' => $parent ? $parent->id : 0,
						'code'      => $code,
					])->get();
					$this->task->log(Logger::INFO, 'Created new category: ' . $current->sysName());

				} else {

					// update exists category
					CategoryAdmin::init($current)->update([
						'name' => $value,
					]);
					$this->task->log(Logger::INFO, 'Update category: ' . $current->sysName());

				}

				// move current category to other parent
				if ($current && $parent && $current->parent_id != $parent->id) {
					CategoryAdmin::init($current)->move2Parent($parent->id);
					$this->task->log(
						Logger::INFO,
						'Change parent category ' . $current->sysName() . ' to ' . $parent->sysName()
					);
				}

				break;

		}


	}


} 