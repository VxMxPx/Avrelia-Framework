<?php

include('base.build.php');

class stylusBuild extends baseBuild
{
	public function execute()
	{
		system('stylus -c -w ' . $this->input . ' -o ' . $this->output);
	}
}

# Finally execute it!
$stylusBuild = new stylusBuild();
$stylusBuild->execute();