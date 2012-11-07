<?php

class baseBuild
{
	protected $input;
	protected $output;
	protected $options;

	protected $baseInput;
	protected $baseOutput;

	public function __construct()
	{
		$this->input_raw = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : false;
		$this->output    = isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : false;
		$this->options   = isset($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : false;

		$this->input     = realpath($this->input_raw);
		$this->baseInput = dirname($this->input);

		$this->baseOutput = dirname($this->output);

		if (!file_exists($this->input)) {
			die("File not found: {$this->input_raw}\n");
		}
	}

	/**
	 * Will output message to screen
	 * --
	 * @param	string	$type		err, war, ok
	 * @param	string	$message
	 * @param	boolean	$newLine	should be message output to the new line
	 * --
	 * @return	void
	 */
	protected function say($type, $message, $newLine=true)
	{
		switch (strtolower($type))
		{
			case 'err':
				$color = "\x1b[31;01m";
				break;

			case 'war':
				$color = "\x1b[33;01m";
				break;

			case 'ok':
				$color = "\x1b[32;01m";
				break;

			default:
				$color = null;
		}

		echo
			(!is_null($color) ? $color : ''),
			$message,
			"\x1b[39;49;00m";

		if ($newLine)
		{
			echo "\n";
		}

		flush();
	}

	/**
	 * Return md5 representing directory and all files in it
	 * --
	 * @param	string	$directory
	 * @param	mixed	$type		Observe only particular type of files,
	 *								False for all!
	 * --
	 * @return	string
	 */
	protected function getMd5($directory, $type=false)
	{
		$finalOutput = '';
		$files = scandir($directory);
		unset($files[0], $files[1]);

		foreach ($files as $file) {
			$newPath = $directory . '/' . $file;
			if (is_dir($newPath)) {
				$finalOutput .= $this->getMd5($newPath);
			}
			else {
				# Do we wanna observe only particular file type?
				if ($type) {
					if (substr($file, -strlen($type)) === $type) {
						continue;
					}
				}
				$finalOutput .= md5_file($newPath);
			}
		}

		return $finalOutput;
	}
}