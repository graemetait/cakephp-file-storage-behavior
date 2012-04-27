# CakePHP File Storage Behavior

A basic file storage behavior for CakePHP 1.x.

Handles storing uploaded files in database or file system.  If uploading to file system will still store metadata in database.

## Installation

1. Copy the behavior to models/behaviors in your app.
2. In your model add:

		public $actsAs = array('FileStorage');

3. Your model's database schema will need fields for filename, type and size, and if storing the file in the db also content. Here is an example schema.

		CREATE TABLE `files` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `filename` varchar(100) NOT NULL,
		  `type` varchar(100) NOT NULL,
		  `size` mediumint(8) unsigned NOT NULL,
		  `content` mediumblob NOT NULL,
		  `created` datetime NOT NULL,
		  `modified` datetime NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

## Usage

When saving this model, if there is a form field named 'file' it will be saved as a file using the behavior. By default it will be saved to the database. The defaults can be changed by passing settings to the behavior as follows.

		public $actsAs = array(
			'FileStorage' => array(
				'storage_type' => 'file',
				'folder' => '/path/to/files'
				'field_name' => 'my_file'
			)
		);