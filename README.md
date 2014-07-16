# CakePHP File Storage Behavior

A basic file storage behavior for CakePHP 2.x. For CakePHP 1.x see the cakephp1 branch.

Handles storing uploaded files in database or filesystem.  If uploading to filesystem will store metadata in database.

Files saved in the filesystem will be saved in a directory hierarchy based on the hash of the file contents.  Filenames are not used, so will never clash.  The hierarchy is to ease performance issues when storing a very large number of files.

## Installation

If you're using composer then just add the following to your require block.

		"burriko/cake-file-storage": "2.1.*@dev"

If you're not, then clone/copy the contents of this directory to app/Plugins/CakeFileStorage.

## Configure

1. Add the following line to your app/Config/bootstrap.php.

		CakePlugin::load('CakeFileStorage');

2. In your model add:

		public $actsAs = array('CakeFileStorage.FileStorage');

3. Your model's database schema will need fields for filename, type and size. If storing the file in the filesystem you will also need one named hash, and if storing in db one named content. Here is an example schema.

		CREATE TABLE `files` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `filename` varchar(100) NOT NULL,
		  `type` varchar(100) NOT NULL,
		  `size` mediumint(8) unsigned NOT NULL,
		  `content` mediumblob NOT NULL,
		  `hash` varchar(40) NOT NULL,
		  `created` datetime NOT NULL,
		  `modified` datetime NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

## Usage

When saving this model, if there is a form field named 'file' it will be saved as a file using the behavior. By default it will be saved to the filesystem in an 'uploads' folder in the root folder of your app. The defaults can be changed by passing settings to the behavior as follows.

		public $actsAs = array(
			'CakeFileStorage.FileStorage' => array(
				'storage_type' => 'filesystem',
				'file_path' => '/path/to/files'
				'field_name' => 'my_file'
			)
		);

The behaviour also provides a validation message to check that the file uploaded without any problems.  This can be used as follows.

		public $validate = array(
			'file' => array(
				'rule' => 'checkFileUpload',
				'message' => 'There was a problem uploading your file.'
			)
		);

You can retrieve the file from the model by using the fetchFile() method, and send the file back as a response by using the downloadFile() method of the FileStorage component.  Here's an example controller that puts those together.

		class DocumentsController extends AppController
		{
			public $components = array('CakeFileStorage.FileStorage');

			public function download($id)
			{
				$document = $this->Document->fetchFile($id);

				return $this->FileStorage->createFileResponse($document);
			}
		}

When deleting a record the file will also be removed from disk, but only if no other record is pointing to the same file (because files are saved under a hash of their contents, if the same file was uploaded for multiple records they would all share the same file on disk).