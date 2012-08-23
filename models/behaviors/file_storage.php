<?php
/**
 * File Storage Behavior
 *
 * Provides functionality for validating and storing file uploads.
 *
 * @copyright     Graeme Tait @burriko
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 **/

class FileStorageBehavior extends ModelBehavior
{
	/**
	 * Storage method. Set to either 'database' or 'file'.
	 * @var string
	 */
	protected $storage_type = 'database';

	/**
	 * The file's form field name. Default is 'file'.
	 * @var string
	 */
	protected $field_name = 'file';

	/**
	 * Folder path if using folder storage.
	 * Use an absolute path with no trailing slash. e.g '/var/www/files'
	 * @var string
	 */
	protected $folder;

	/**
	 * Reads settings from model
	 * @param array $settings Settings from model
	 */
	public function setup(Model $model, $settings)
	{
		// load settings
		foreach ($settings as $setting => $value) {
			$this->{$setting} = $value;
		}
	}

	/**
	 * Validation method for checking that file has uploaded correctly
	 * @return mixed True if uploaded successfully, else an error message
	 */
	public function checkFileUpload(Model $model, $check)
	{
		$error_code = $check[$this->field_name]['error'];

		if ($error_code == 0)
			return true;

		switch ($error_code) {
			case 1:
			case 2:
				$message = 'The file cannot be larger than ' . ini_get('upload_max_filesize') . '.';
				break;
			case 4:
				$message = 'You must select a file to upload.';
				break;
			default:
				$message = 'There was a problem uploading your file.';
		}

		return $message;
	}

	/**
	 * Called automatically when model is saved. Attempts to save file.
	 * @return bool Whether file has saved successfully
	 */
	public function beforeSave(Model $model)
	{
		$file_data = $this->getFileDataFromForm($model);
		return $this->storeFile($model, $file_data);
	}

	/**
	 * Calls the appropriate method to save the file depending on storage type.
	 * @param  array $file_data The file's form data
	 * @return bool             Whether file has saved successfully
	 */
	protected function storeFile($model, $file_data)
	{
		switch ($this->storage_type) {
			case 'database':
				$file_saved = $this->addFileContentToModel($model, $file_data);
				break;
			case 'file':
				$file_saved = $this->storeFileInFolder($file_data);
				break;
		}

		if ($file_saved)
			$this->addFileMetaDataToModel($model, $file_data);

		return $file_saved;
	}

	/**
	 * Fetches the file fields from the form
	 * @return array File form data
	 */
	protected function getFileDataFromForm($model)
	{
		$file_data = $model->data[$model->name][$this->field_name];

		// Remove raw file form fields from the model
		unset($model->data[$model->name][$this->field_name]);

		return $file_data;
	}

	/**
	 * Adds meta data about the file to the model
	 * @param array $file_data Meta data about the file
	 */
	protected function addFileMetaDataToModel($model, $file_data)
	{
		$model->data[$model->name]['filename'] = $file_data['name'];
		$model->data[$model->name]['type'] = $file_data['type'];
		$model->data[$model->name]['size'] = $file_data['size'];
	}

	/**
	 * Saves file contents to database.
	 * @param array $file_data The file data
	 * @return bool            Whether successful
	 */
	protected function addFileContentToModel($model, $file_data)
	{
		return (bool) $model->data[$model->name]['content']
			= file_get_contents($file_data['tmp_name']);
	}

	/**
	 * Saves file in folder.
	 * @param array $file_data The file data
	 * @return bool            Whether successful
	 */
	protected function storeFileInFolder($file_data)
	{
		if (!(is_dir($this->folder) and is_writable($this->folder)))
			return false;

		return move_uploaded_file($file_data['tmp_name'], $this->folder . DS . $file_data['name']);
	}
}