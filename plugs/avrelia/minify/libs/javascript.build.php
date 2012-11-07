<?php

include('base.build.php');

class javascriptBuild extends baseBuild
{
    private $outputMin;
    private $rawJs;

    public function execute()
    {
        $filename = basename($this->output);
        $filename = explode('.', strrev($filename), 2);
        $filename = strrev($filename[1]);

        $this->outputMin = dirname($this->output) . '/' . $filename . '.min.js';
        $this->rawJs     = dirname($this->output) . '/' . $filename . '.js';

        $calcMod = null;

        do {
            $r = $this->getMd5($this->baseInput);
            if ($calcMod != $r) {
                $calcMod = $r;
                $files   = json_decode(file_get_contents($this->input), true);
                $contents = $this->_merge_files($files);

                if (file_put_contents($this->output, $contents)) {
                    $this->say('INF', '  JavaScript to: ' . $this->output);
                    if (strpos($this->options, 'coffe') !== false) {
                        system('coffee --compile ' . $this->output);
                    }
                    if (strpos($this->options, 'uglify') !== false) {
                        system("uglifyjs -o {$this->outputMin} {$this->rawJs}");
                    }
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
     * Merge multiple files into one file.
     * --
     * @param  array $files
     * --
     * @return string
     */
    protected function _merge_files($files)
    {
        $result = '';

        if (is_array($files)) {
            foreach ($files as $file) {
                
                if (substr($file, 0, 1) === '.') { continue; }

                $fullpath = $this->baseInput . '/' . trim($file);
                
                if (file_exists($fullpath)) {
                    $result .= "\n\n" . file_get_contents($fullpath);
                }
                else {
                    $this->say('WAR', "File not found: {$fullpath}");
                }
            }
        }
        else {
            $this->say('WAR', 'Not an array passed to merge files!');
        }

        return $result;
    }
}

# Finally execute it!
$javascriptBuild = new javascriptBuild();
$javascriptBuild->execute();