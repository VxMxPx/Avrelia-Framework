<?php

include('base.build.php');

class javascriptBuild extends baseBuild
{
	private $outputMin;
	private $rawJs;

	public function execute()
	{
		$filename = basename($this->output);
		$filename = explode('.', $filename);
		$filename = $filename[0];

		$this->outputMin = dirname($this->output) . '/' . $filename . '.min.js';
		$this->rawJs     = dirname($this->output) . '/' . $filename . '.js';

		$calcMod = null;

		do {
			$r = $this->getMd5($this->baseInput);
			if ($calcMod != $r) {
				$calcMod = $r;
				$contents = file_get_contents($this->input);
				$contents = preg_replace_callback(
								'/\/\/@include (.*?)\s/', 
								array($this, 'setIncludes'), 
								$contents);

				if (file_put_contents($this->output, $contents)) {
					$this->say('INF', '  JavaScript to: ' . $this->output);
					dump($this->output);
					//system('coffee --compile ' . $this->output);
					//system("uglifyjs -o {$this->outputMin} {$this->rawJs}");
				}
				else {
					$this->say('ERR', "Failed: {$output}");
				}
			}
			sleep(2);
		}
		while(1 == 1); # Forever!
	}

	/**
	 * Will replace #@include path/to/file with actual file content
	 * --
	 * @param	array	$match
	 * --
	 * @return	string
	 */
	private function setIncludes($match)
	{
		$fullpath = $this->baseInput . '/' . trim($match[1]);

		if (file_exists($fullpath)) {
			return "\n\n" . file_get_contents($fullpath);
		}
		else {
			$this->say('WAR', "File not found: {$fullpath}");
		}
	}
}

# Finally execute it!
$javascriptBuild = new javascriptBuild();
$javascriptBuild->execute();