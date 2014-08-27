<?php

require(App::pluginPath('CakeFileStorage') . 'Test' . DS . 'Model' . DS . 'TestModel.php');

App::uses('FilesystemStorageType', 'CakeFileStorage.StorageType');

class FilesystemStorageTypeTest extends CakeTestCase
{
	public function testFetchFileMetaData()
	{
		$id = 34;
		$model_data = array('TestModel' => array(
			'hash' => 'hw7efy29h7ed29d8ed2'
		));

		$model = $this->getMockForModel('TestModel', array('findById'));
		$model->expects($this->once())
		      ->method('findById')
		      ->with($id, array('id', 'filename', 'type', 'size', 'hash'))
		      ->will($this->returnValue($model_data));

		$storage_type = new FilesystemStorageType($model, array('file_path' => '/file/path'));
		$meta_data = $storage_type->fetchFileMetaData($id);

		$this->assertEqual(array(
			'hash' => 'hw7efy29h7ed29d8ed2',
			'path' => '/file/path/test_models/hw/7e/hw7efy29h7ed29d8ed2'
		), $meta_data);
	}

	public function testFetchFileMetaDataNoRecord()
	{
		$id = 34;

		$model = $this->getMockForModel('TestModel', array('findById'));
		$model->expects($this->once())
		      ->method('findById')
		      ->with($id, array('id', 'filename', 'type', 'size', 'hash'))
		      ->will($this->returnValue(false));

		$storage_type = new FilesystemStorageType($model, array('file_path' => '/file/path'));
		$meta_data = $storage_type->fetchFileMetaData($id);

		$this->assertEqual(false, $meta_data);
	}
}