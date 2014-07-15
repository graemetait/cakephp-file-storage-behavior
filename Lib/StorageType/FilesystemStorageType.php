<?php

App::uses('StorageTypeInterface', 'CakeFileStorage.StorageType');
App::uses('InvalidStoragePathException', 'CakeFileStorage.Exception');

class FilesystemStorageType implements StorageTypeInterface
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
		$fields = array('id', 'filename', 'type', 'size', 'hash');
		if ($record = $this->model->findById($id, $fields)) {
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
		$file_path = $this->settings['file_path'];
		$file_path .= $this->generatePathFromHash($meta_data['hash']);

		if ( ! is_readable($file_path)) {
			return false;
		}

		return file_get_contents($file_path);
	}

	/**
	 * Store file
	 *
	 * @param array $file_data
	 * @return bool
	 */
	public function storeFile($file_data)
	{
		$folder = $this->settings['file_path'];

		if ( ! $this->isValidStorageFolder($folder)) {
			throw new InvalidStoragePathException('Attempting to store file in an invalid path');
		}

		$file_data['hash'] = $this->hashFile($file_data['tmp_name']);

		$path_and_filename = $folder . $this->generatePathFromHash($file_data['hash']);
		$file_saved = $this->storeFileInFolder($file_data['tmp_name'], $path_and_filename);

		if ($file_saved) {
			$this->addFileMetaDataToModel($file_data);
		}

		return $file_saved;
	}

	protected function storeFileInFolder($tmp_name, $real_name)
	{
		if ( ! $this->isValidStorageFolder(dirname($real_name))) {
			if ( ! mkdir(dirname($real_name), 0755, true)) {
				throw new InvalidStoragePathException('Failed to create directory for uploaded file');
			}
		}

		return move_uploaded_file($tmp_name, $real_name);
	}

	protected function isValidStorageFolder($folder)
	{
		return is_dir($folder) and is_writable($folder);
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
		$this->model->data[$this->model->name]['hash'] = $file_data['hash'];
	}

	protected function hashFile($file_name)
	{
		return sha1($this->openFile($file_name));
	}

	protected function openFile($file_name)
	{
		return file_get_contents($file_name);
	}

	protected function generatePathFromHash($hash)
	{
		$first_level = substr($hash, 0, 2);
		$second_level = substr($hash, 2, 2);

		return '/' . $first_level . '/' . $second_level . '/' . $hash;
	}
}