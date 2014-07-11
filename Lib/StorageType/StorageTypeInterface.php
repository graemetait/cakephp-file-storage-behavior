<?php

interface StorageTypeInterface
{
	/**
	 * Fetch a file's meta data
	 *
	 * @param  int $id Record id
	 * @return array   File meta data
	 */
	public function fetchFileMetaData($id);

	/**
	 * Fetch a file's contents
	 *
	 * @param  array $meta_data File meta data
	 * @return string           File contents
	 */
	public function fetchFileContents($meta_data);

	/**
	 * Store file
	 *
	 * @param array $file_data
	 * @return bool
	 */
	public function storeFile($file_data);
}