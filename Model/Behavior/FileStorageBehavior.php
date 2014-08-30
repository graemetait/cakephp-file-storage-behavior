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

App::uses('FileStorage', 'CakeFileStorage.Lib');
App::uses('InvalidFileFormFieldException', 'CakeFileStorage.Exception');

class FileStorageBehavior extends ModelBehavior
{
	/**
	 * Default settings, each of which can be overridden in the model.
	 * - storage_type: string - 'filesystem' or 'database'.
	 * - field_name:   string - Name of the file input form field.
	 * - file_path:    string - Directory path if using file storage. Use an absolute
	 *                          path with no trailing slash e.g '/var/www/files'.
	 *                          Default is ROOT/uploads
	 *
	 * @var array
	 */
	protected $default_settings = array(
		 'storage_type' => 'filesystem',
		 'field_name'   => 'file',
		 'file_path'    => ''
	);

	/**
	 * Reads settings from model
	 *
	 * @param array $config Settings from model
	 */
	public function setup(Model $model, $config = array())
	{
		$this->default_settings['file_path'] = $this->defaultFolderPath();

		// load settings
		$this->settings[$model->alias] = $this->default_settings;
		foreach ($config as $setting => $value) {
			$this->settings[$model->alias][$setting] = $value;
		}

		$this->file_storage[$model->alias] = new FileStorage(
			$model,
			$this->settings[$model->alias]
		);
	}

	/**
	 * Validation method for checking that file has uploaded correctly
	 *
	 * @return mixed True if uploaded successfully, else an error message
	 */
	public function checkFileUpload(Model $model, $check)
	{
		$field_name = $this->getSetting($model, 'field_name');
		$error_code = $check[$field_name]['error'];

		if ($error_code == 0) {
			return true;
		}

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
	 * Called automatically when model is saved. Attempts to save file
	 *
	 * @return bool Whether file has saved successfully
	 */
	public function beforeSave(Model $model, $options = array())
	{
		if ($file_data = $this->getFileDataFromForm($model)) {

			$file_storage = $this->file_storage[$model->alias];

			return $file_storage->storeFile($file_data);
		}

		return true;
	}

	public function beforeDelete(Model $model, $cascade = true)
	{
		$file_storage = $this->file_storage[$model->alias];

		return $file_storage->deleteFile($model->id);
	}

	/**
	 * Fetch the file data by record id
	 *
	 * @param  int $id Record id
	 * @return array   File meta data and contents
	 */
	public function fetchFile(Model $model, $id)
	{
		$file_storage = $this->file_storage[$model->alias];

		return $file_storage->fetchFile($id);
	}

	/**
	 * Fetch a file's meta data without the file contents
	 *
	 * @param  int $id Record id
	 * @return array   File meta data
	 */
	public function fetchFileMetaData(Model $model, $id)
	{
		$file_storage = $this->file_storage[$model->alias];

		return $file_storage->fetchFileMetaData($id);
	}

	/**
	 * Fetches the file fields from the form
	 *
	 * @return array File form data
	 */
	protected function getFileDataFromForm($model)
	{
		$field_name = $this->getSetting($model, 'field_name');
		if (isset($model->data[$model->name][$field_name])) {

			if ( ! is_array($model->data[$model->name][$field_name])) {
				throw new InvalidFileFormFieldException(
					'Check that form is multipart'
				);
			}

			$file_data = $model->data[$model->name][$field_name];
		} else {
			$file_data = false;
		}

		// Remove raw file form fields from the model
		unset($model->data[$model->name][$field_name]);

		return $file_data;
	}

	/**
	 * Adds meta data about the file to the model
	 *
	 * @param array $file_data Meta data about the file
	 */
	protected function addFileMetaDataToModel($model, $file_data)
	{
		$model->data[$model->name]['filename'] = $file_data['name'];
		$model->data[$model->name]['type'] = $file_data['type'];
		$model->data[$model->name]['size'] = $file_data['size'];
	}

	/**
	 * Retrieve the setting for the specified model
	 *
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
	 *
	 * @return string Path of folder to save files
	 */
	protected function defaultFolderPath()
	{
		$path = defined('ROOT') ? ROOT : getcwd();
		$path .= DS . 'uploads';
		return $path;
	}
}
