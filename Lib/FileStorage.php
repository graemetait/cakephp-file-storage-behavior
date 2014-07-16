<?php

App::uses('DatabaseStorageType', 'CakeFileStorage.StorageType');
App::uses('FilesystemStorageType', 'CakeFileStorage.StorageType');

class FileStorage
{
	protected $model;

	protected $settings;

	protected $storage;

	public function __construct(Model $model, array $config)
	{
		$this->model = $model;
		$this->settings = $config;

		$this->storage = $this->storageTypeFactory($config['storage_type']);
	}

	private function storageTypeFactory($storage_type)
	{
		switch ($storage_type) {
			case 'database':
				return new DatabaseStorageType($this->model, $this->settings);
			case 'filesystem':
				return new FilesystemStorageType($this->model, $this->settings);
		}
	}

	/**
	 * Fetch the file data by record id
	 *
	 * @param  int $id Record id
	 * @return array   File meta data and contents
	 */
	public function fetchFile($id)
	{
		if ( ! $file_data = $this->storage->fetchFileMetaData($id)) {
			return false;
		}

		$file_data['content'] = $this->storage->fetchFileContents($file_data);

		if (empty($file_data['content'])) {
			return false;
		}

		return $file_data;
	}

	/**
	 * Fetch a file's meta data without the file contents
	 *
	 * @param  int $id Record id
	 * @return array   File meta data
	 */
	public function fetchFileMetaData($id)
	{
		return $this->storage->fetchFileMetaData($id);
	}

	/**
	 * Calls the appropriate method to save the file depending on storage type
	 *
	 * @param  array $file_data The file's form data
	 * @return bool             Whether file has saved successfully
	 */
	public function storeFile($file_data)
	{
		return $this->storage->storeFile($file_data);
	}

	public function deleteFile($id)
	{
		return $this->storage->deleteFile($id);
	}

	protected function getSetting($setting)
	{
		return $this->settings[$setting];
	}
}