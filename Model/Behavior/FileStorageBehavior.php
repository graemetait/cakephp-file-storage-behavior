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
	 * Default settings, each of which can be overridden in the model.
	 * - storage_type: string - Set to either 'file' or 'database'.
	 * - field_name: string - Name of the file input form field.
	 * - file_path: string - Directory path if using file storage. Set by the
	 *    function defaultFolderPath().  Should be <path to cake app>/uploads.
	 *    Use an absolute path with no trailing slash. e.g '/var/www/files'
	 * @var array
	 */
	protected $default_settings = array(
		 'storage_type' => 'file',
		 'field_name' => 'file',
		 'file_path' => ''
	);

	/**
	 * Reads settings from model
	 * @param array $settings Settings from model
	 */
	public function setup(Model $model, $settings)
	{
		$this->default_settings['file_path'] = $this->defaultFolderPath();

		// load settings
		$this->settings[$model->alias] = $this->default_settings;
		foreach ($settings as $setting => $value) {
			$this->settings[$model->alias][$setting] = $value;
		}
	}

	/**
	 * Validation method for checking that file has uploaded correctly
	 * @return mixed True if uploaded successfully, else an error message
	 */
	public function checkFileUpload(Model $model, $check)
	{
		$field_name = $this->getSetting($model, 'field_name');
		$error_code = $check[$field_name]['error'];

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
	 * Fetch the file data by record id
	 * @param  int $id Record id
	 * @return array   File meta data and contents
	 */
	public function fetchFile(Model $model, $id)
	{
		if (!$model_data = $model->findById($id))
			return false;

		$file_data = $model_data[$model->alias];

		if ($this->getSetting($model, 'storage_type') == 'file') {
			$file_data['content'] = $this->fetchFileContents(
				$model,
				$file_data['filename']
			);
		}

		if (empty($file_data['content']))
			return false;
		return $file_data;
	}

	/**
	 * Fetch a file's meta data without the file contents
	 * @param  int $id Record id
	 * @return array   File meta data
	 */
	public function fetchFileMetaData(Model $model, $id)
	{
		return $model->findById($id, array('id', 'filename', 'size'));
	}

	/**
	 * Returns the contents of a file from the filesystem
	 * @param  string $filename Name of file
	 * @return string           File contents
	 */
	protected function fetchFileContents(Model $model, $filename)
	{
		$file_path = $this->getSetting($model, 'file_path');
		$file_path .= DS . $filename;
		if (!is_readable($file_path))
			return false;
		return file_get_contents($file_path);
	}

	/**
	 * Calls the appropriate method to save the file depending on storage type.
	 * @param  array $file_data The file's form data
	 * @return bool             Whether file has saved successfully
	 */
	protected function storeFile($model, $file_data)
	{
		switch ($this->getSetting($model, 'storage_type')) {
			case 'database':
				$file_saved = $this->addFileContentToModel($model, $file_data);
				break;
			case 'file':
				$file_saved = $this->storeFileInFolder($model, $file_data);
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
		$field_name = $this->getSetting($model, 'field_name');
		$file_data = $model->data[$model->name][$field_name];

		// Remove raw file form fields from the model
		unset($model->data[$model->name][$field_name]);

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
	protected function storeFileInFolder($model, $file_data)
	{
		$folder = $this->getSetting($model, 'file_path');
		if (!(is_dir($folder) and is_writable($folder)))
			return false;

		return move_uploaded_file($file_data['tmp_name'], $folder . DS . $file_data['name']);
	}

	/**
	 * Retrieve the setting for the specified model
	 * @param  string $setting_name The name of the setting
	 * @return mixed                The setting's value for this model
	 */
	protected function getSetting($model, $setting_name)
	{
		return $this->settings[$model->alias][$setting_name];
	}

	/**
	 * Attempt to come up with a sensible default path for saving files.
	 * Should be <path to cake app>/uploads
	 * @return string Path of folder to save files
	 */
	protected function defaultFolderPath()
	{
		$path = defined('ROOT') ? ROOT : getcwd();
		$path .= DS . 'uploads';
		return $path;
	}
}