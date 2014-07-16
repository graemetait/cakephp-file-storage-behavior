<?php

App::uses('StorageTypeInterface', 'CakeFileStorage.StorageType');

class DatabaseStorageType implements StorageTypeInterface
{
	protected $model;

	protected $settings;

	public function __construct(Model $model, array $config)
	{
		$this->model = $model;
		$this->settings = $config;
	}

	/**
	 * Fetch a file's meta data without the file contents
	 *
	 * @param  int $id Record id
	 * @return array   File meta data
	 */
	public function fetchFileMetaData($id)
	{
		if ($record = $this->model->findById($id, array('id', 'filename', 'type', 'size'))) {
			return $record[$this->model->alias];
		} else {
			return false;
		}
	}

	/**
	 * Fetch a file's contents
	 *
	 * @param  array $meta_data File meta data
	 * @return string           File contents
	 */
	public function fetchFileContents($meta_data)
	{
		$model_data = $this->model->findById($meta_data['id']);
		return $model_data[$this->model->alias]['content'];
	}

	/**
	 * Store file
	 *
	 * @param array $file_data
	 * @return bool
	 */
	public function storeFile($file_data)
	{
		$file_saved = $this->addFileContentToModel($file_data['tmp_name']);

		if ($file_saved) {
			$this->addFileMetaDataToModel($file_data);
		}

		return $file_saved;
	}

	/**
	 * Delete file
	 *
	 * @param  int $id Record id
	 * @return bool
	 */
	public function deleteFile($id)
	{
		return true;
	}

	/**
	 * Saves file contents to database.
	 *
	 * @param array $file_contents
	 * @return bool
	 */
	protected function addFileContentToModel($file_contents)
	{
		return (bool) $this->model->data[$this->model->name]['content']
			= file_get_contents($file_contents);
	}

	/**
	 * Adds meta data about the file to the model
	 *
	 * @param array $file_data Meta data about the file
	 */
	protected function addFileMetaDataToModel($file_data)
	{
		$this->model->data[$this->model->name]['filename'] = $file_data['name'];
		$this->model->data[$this->model->name]['type'] = $file_data['type'];
		$this->model->data[$this->model->name]['size'] = $file_data['size'];
	}
}