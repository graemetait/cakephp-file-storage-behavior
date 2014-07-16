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
		$file_path = $this->generateFullPathToFile($meta_data['hash']);

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
		if ( ! $this->isValidStorageFolder($this->settings['file_path'])) {
			throw new InvalidStoragePathException('Attempting to store file in an invalid path');
		}

		$file_data['hash'] = $this->hashFile($file_data['tmp_name']);

		$full_path = $this->generateFullPathToFile($file_data['hash']);
		$file_saved = $this->storeFileInFolder($file_data['tmp_name'], $full_path);

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
		$file_data = $this->fetchFileMetaData($id);

		// If other records use this file then don't delete it
		if ($this->countFilesWithHash($file_data['hash']) > 1) {
			return true;
		}

		$full_path = $this->generateFullPathToFile($file_data['hash']);

		return $this->unlink($full_path);
	}

	protected function countFilesWithHash($hash)
	{
		return $this->model->find('count', array(
			'conditions' => array('hash' => $hash)
		));
	}

	protected function unlink($file)
	{
		$folder = $this->settings['file_path'];

		// A few safety checks

		// If not in uploads folder, then abort
		if (strpos($file, $folder) !== 0) {
			return false;
		}

		if ( ! is_file($file)) {
			return false;
		}

		$file_deleted = unlink($file);

		// Tidy up possibly-empty folders
		if ($this->deleteEmptyFolder(dirname($file))) {
			$this->deleteEmptyFolder(dirname(dirname($file)));
		}

		return $file_deleted;
	}

	protected function deleteEmptyFolder($folder)
	{
		if (is_dir($folder) and $this->isFolderEmpty($folder)) {
			return rmdir($folder);
		}

		return false;
	}

	protected function isFolderEmpty($folder)
	{
		// 2 because of . and ..
		return count(scandir($folder)) <= 2;
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

	protected function generateFullPathToFile($hash)
	{
		$full_path = $this->settings['file_path'];
		$full_path .= $this->generatePathFromModelName($this->model->alias);
		$full_path .= $this->generatePathFromHash($hash);

		return $full_path;
	}

	protected function generatePathFromModelName($model_name)
	{
		return '/' . Inflector::tableize($model_name);
	}

	protected function generatePathFromHash($hash)
	{
		$first_level = substr($hash, 0, 2);
		$second_level = substr($hash, 2, 2);

		return '/' . $first_level . '/' . $second_level . '/' . $hash;
	}
}