<?php

class FileStorageComponent extends Component
{
	protected $controller;

	public function initialize(Controller $controller)
	{
		$this->controller = $controller;
	}

	/**
	 * Tell Cake to send the file to the client
	 *
	 * @param  array $file_data      File data from fetchFile() method
	 * @param  bool  $force_download Whether browser should save or open file
	 * @return CakeResponse
	 */
	public function downloadFile($file_data, $force_download = true)
	{
		$this->controller->response->type($file_data['type']);
		$this->controller->response->body($file_data['content']);

		if ($force_download) {

			$this->controller->response->download($file_data['filename']);

		} else {

			$this->controller->response->header(
				'Content-Disposition',
				'inline; filename="' . $file_data['filename'] . '"'
			);

		}

		return $this->controller->response;
	}
}