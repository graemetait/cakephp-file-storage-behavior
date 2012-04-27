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
	protected
		$storage_type = 'database', // storage method, either 'database' or 'file'
		$field_name = 'file', // name of the file's form field
		$folder; // folder path if using folder storage

	public function setup($model, $settings)
	{
		// load settings
		foreach ($settings as $setting => $value) {
			$this->{$setting} = $value;
		}
	}

	// validation method for checking that file has uploaded correctly
	public function checkFileUpload($model, $check)
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

	public function beforeSave($model)
	{
		$file_data = $this->getFileDataFromForm($model);
		return $this->storeFile($model, $file_data);
	}

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

	protected function getFileDataFromForm($model)
	{
		$file_data = $model->data[$model->name][$this->field_name];

		// don't want raw file form fields in the model anymore
		unset($model->data[$model->name][$this->field_name]);

		return $file_data;
	}

	protected function addFileMetaDataToModel($model, $file_data)
	{
		$model->data[$model->name]['filename'] = $file_data['name'];
		$model->data[$model->name]['type'] = $file_data['type'];
		$model->data[$model->name]['size'] = $file_data['size'];
	}

	protected function addFileContentToModel($model, $file_data)
	{
		return (bool) $model->data[$model->name]['content'] = file_get_contents($file_data['tmp_name']);
	}

	protected function storeFileInFolder($file_data)
	{
		if (!(is_dir($this->folder) and is_writable($this->folder)))
			return false;

		return move_uploaded_file($file_data['tmp_name'], $this->folder . DS . $file_data['name']);
	}
}