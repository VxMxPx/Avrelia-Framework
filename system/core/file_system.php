<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * File System
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2009, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      Fri Apr 01 16:29:40 2011
 */

class FileSystem
{

	/**
	 * Get file's content
	 * ---
	 * @param string $fileName
	 * @param string $create  -- create file, if doesn't exists
	 * ---
	 * @return string
	 */
	public static function Read($fileName, $create=false)
	{
		$fileName = ds($fileName);

		if (file_exists($fileName) and !is_dir($fileName)) {
			Log::Add('INF', "File is valid: `{$fileName}`.", __LINE__, __FILE__);
			if (!$contents = file_get_contents($fileName, 1)) {
				Log::Add('ERR', "Error while reading file: `{$fileName}`.", __LINE__, __FILE__);
				return false;
			}
			return $contents;
		}
		elseif ($create) {
			Log::Add('INF', "File doesn't exists: `{$fileName}`, we'll try to create it.", __LINE__, __FILE__);
			self::Write('', $fileName);
			return '';
		}
		else {
			Log::Add('ERR', "Not a valid file: `{$fileName}`.", __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Delete All Files (of selected type) In Selected Folder, will return number of deleted files
	 * ---
	 * @param string $fullPath -- if the path is directory, the whole directory will be removed
	 * @param string $filter   -- use '*', for all files, '*-something' or 'something.*', enter filename - to just delete file
	 * 								leave false, if you want to remove only one file or directory
	 * ---
	 * @return integer
	 */
	public static function Remove($fullPath, $filter=false)
	{
		if (is_dir($fullPath) || $filter==false) {
			$path = $fullPath;
			$num  = 0;

			if (is_dir($path))
			{
				$Entries = scandir($path);
				foreach ($Entries as $entry) {
					if ($entry != '.' && $entry != '..') {
						$num = $num + self::Remove(ds($path.'/'.$entry));
					}
				}
				if (rmdir($path)) {
					Log::Add('INF', "Folder was removed: `{$path}`.", __LINE__, __FILE__);
					$num++;
				}
				else {
					Log::Add('WAR', "Folder was NOT removed: `{$path}`.", __LINE__, __FILE__);
				}
			}
			else {
				if (unlink($path)) {
					Log::Add('INF', "File deleted: `{$path}`.", __LINE__, __FILE__);
					$num++;
				}
				else {
					Log::Add('WAR', "Can't delete file: `{$path}`.", __LINE__, __FILE__);
				}
			}

			return $num;
		}
		else {
			// It's not a direcotry, so we'll remove files
			$fullPath = ds($fullPath);

			Log::Add('INF', "Request for removing files with filter: `{$filter}` in folder: `{$fullPath}`.", __LINE__, __FILE__);

			if ($filter == '*') {
				$type = 'all';
			}
			elseif ((substr($filter,0,1) == '*') && (substr($filter,-1,1) == '*') ) {
				$type   = 'mid';
				$string = substr($filter,1,-1);
			}
			elseif (substr($filter,0,1) == '*') {
				$type   = 'end';
				$string = substr($filter,1);
				$len    = strlen($string);
			}
			elseif (substr($filter,-1,1) == '*') {
				$type   = 'start';
				$string = substr($filter,0,-1);
				$len    = strlen($string);
			}
			else {
				Log::Add('WAR', "No filter provided; the file: `{$filter}`, will be deleted.", __LINE__, __FILE__);
				return unlink(ds($fullPath.'/'.$filter));
			}

			$i = 0;

			if (is_dir($fullPath)) {
				$Entries = scandir($fullPath);
				foreach ($Entries as $entry) {
					if ($entry != '.' && $entry != '..') {
						if (($type == 'all') ||
							( ($type == 'mid') && (strpos($entry, $string)) ) OR
							( ($type == 'end') && (substr($entry,-$len,$len) == $string) ) OR
							( ($type == 'start') && (substr($entry,0,$len) == $string) )) {
							self::Remove(ds($fullPath.'/'.$entry), $filter);
							$i++;
						}
					}
				}
			}
			else {
				unlink($path);
				return 1;
			}

			return $i;
		}
	}
	//-

	/**
	 * Rename File, please provide full name for both or none of them!
	 * ---
	 * @param string $oldName   -- full path with file name, or only filename
	 * @param string $newName   -- full path with file name, or only filename
	 * @param string $directory -- if you din't provide full path with 'oldName'
	 * 								AND with 'newName', provided it here!
	 * ---
	 * @return bool
	 */
	public static function Rename($oldName, $newName, $directory=false)
	{
		if ($directory) {
			$oldName = ds($directory . '/' . $oldName);
			$newName = ds($directory . '/' . $newName);
		}
		else {
			$oldName = ds($oldName);
			$newName = ds($newName);
		}

		if (rename($oldName, $newName)) {
			Log::Add('INF', "File was renamed from: `{$oldName}`, to `{$newName}`.", __LINE__, __FILE__);
			return true;
		}
		else {
			Log::Add('ERR', "Error while renaming file: `{$oldName}`, to `{$newName}`.", __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Save Content To File
	 * ---
	 * @param string $content
	 * @param string $fileName   -- full path + file name
	 * @param bool   $fileAppend -- add data to existing file
	 * @param bool   $makeDir    -- create directory if doesn't exists (set mask or false)
	 * ---
	 * @return bool
	 */
	public static function Write($content, $fileName, $fileAppend=false, $makeDir=0755)
	{
		$fileName = ds($fileName);

		# Check For File Append
		if ($fileAppend) {
			$fileAppend = FILE_APPEND;
		}
		else {
			$fileAppend = 0;
		}

		# Delete It If Exists
		if ($fileAppend === 0) {
			if (file_exists($fileName)) unlink($fileName);
		}

		# Create directrory if doesn't exists...
		if ($makeDir !== false) {
			$directory = dirname($fileName);
			if (!is_dir($directory)) {
				self::MakeDir($directory, true, $makeDir);
			}
		}

		if ($filePointer = fopen($fileName, ($fileAppend ? 'a' : 'w'))) {
			if (fwrite($filePointer, $content) === false) {
				Log::Add('ERR', "Filed to write to file: `{$fileName}`.", __LINE__, __FILE__);
				return false;
			}

			if (fclose($filePointer) !== false) {
				Log::Add('INF', "Saved: `{$fileName}`.", __LINE__, __FILE__);
				return true;
			}
			else {
				Log::Add('ERR', "Filed to close file: `{$fileName}`.", __LINE__, __FILE__);
				return false;
			}
		}
		else {
			Log::Add('ERR', "Faild to open file: `{$fileName}`.", __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Copy File...
	 * ---
	 * @param string $source      -- source fullpath + file / if is dir, the whole folder will be copied
	 * @param string $destination -- must be only destination folder (full path)
	 * @param string $newFileName -- if false, filename will be taken from source!
	 * @param string $exists -- If file exists: false|'replace'|'rename' (return false | replace original file | rename current file)
	 * ---
	 * @return bool
	 */
	public static function Copy($source, $destination, $newFileName=false, $exists=false)
	{
		# Should we ignore it?
		$nameOnly = basename($source);
		if (in_array($nameOnly, Cfg::Get('System/ignore_on_copy', array()))) {
			Log::Add('INF', "This file/folder was set to be ignored on copy: `{$nameOnly}`!", __LINE__, __FILE__);
			return true;
		}

		if (is_dir($source)) {
			$num = 0;

			if (!is_dir($destination)) {
				if (self::MakeDir($destination)) {
					Log::Add('INF', "Folder was created: `{$destination}`.", __LINE__, __FILE__);
				}
				else {
					Log::Add('ERR', "Error while creating folder: `{$destination}`.", __LINE__, __FILE__);
				}
			}

			$d = dir($source);

			while (false !== ($entry = $d->read()))
			{
				if ($entry == '.' || $entry == '..') continue;

				$Entry = $source . '/' . $entry;

				if (is_dir($Entry)) {
					$num = $num + self::Copy($Entry, $destination.'/'.$entry);
					continue;
				}
				if (!file_exists($destination.'/'.$entry)) {
					if (!copy($Entry, $destination.'/'.$entry)) {
						Log::Add('ERR', "Error on copy: `{$Entry}` to: `{$destination}/{$entry}`.", __LINE__, __FILE__);
					}
					else {
						$num++;
					}
				}
			}

			$d->close();

			return $num;
		}
		else {
			// Copy Files...
			if (!file_exists($source)) {
				Log::Add('WAR', "Source file doesn't exists: `{$source}`.", __LINE__, __FILE__);
				return false;
			}

			if (!is_dir($destination)) {
				Log::Add('WAR', "Destination isn't directory: `{$destination}`.", __LINE__, __FILE__);
				return false;
			}

			# Get source file name
			if (!$newFileName) {
				$sourceFileName = self::FileName($source);
			}
			else {
				$sourceFileName = $newFileName;
			}

			if (file_exists(ds($destination.'/'.$sourceFileName))) {
				Log::Add('INF', "File exists: `".ds($destination.'/'.$sourceFileName).'`.', __LINE__, __FILE__);

				if ($exists === false) {
					Log::Add('WAR', "File exists, we'll return false.", __LINE__, __FILE__);
					return false;
				}
				elseif($exists == 'replace') {
					Log::Add('INF', "We'll replace original file.", __LINE__, __FILE__);
				}
				elseif ($exists == 'rename') {
					$sourceFileName = self::UniqueName($sourceFileName, $destination);
				}
			}

			if (!self::IsWritable($destination)) {
				Log::Add('WAR', "Destination folder isn't writable: `{$destination}`.", __LINE__, __FILE__);
				return false;
			}

			if (copy($source, ds($destination.'/'.$sourceFileName))) {
				Log::Add('INF', "File was copied: `{$source}`, to: `".ds($destination.'/'.$sourceFileName).'`.', __LINE__, __FILE__);
				return true;
			}
			else {
				Log::Add('ERR', "Error, can't copy file: `{$source}`, to: `".ds($destination.'/'.$sourceFileName).'`.', __LINE__, __FILE__);
				return false;
			}
		}
	}
	//-

	/**
	 * Will create unique filename. This will check if file/folder with provided name already exists.
	 * If you're using this on file, the system will keep the extention, so you can enter $baseFilename with it!
	 *
	 * @param string $baseFilename   - only filename, not full path!
	 * @param string $destinationDir - full absolute destination path
	 * @param bool   $isFile         - are we generating UniqueName for file or folder?
	 * @param string $divider        - divider for new filename, example:
	 * 								   if divider is "_" then mynewfile will become mynewfile_1
	 *
	 * @return string
	 */
	public static function UniqueName($baseFilename, $destinationDir, $isFile=true, $divider='_')
	{
		if ($isFile)
		{
			if (file_exists(ds($destinationDir."/{$baseFilename}"))) {
				$ext  = self::Extension($baseFilename);
				$base = self::FileName($baseFilename, true);
				$n    = 1;
				do {
					$baseFilename = $base . $divider . $n . (empty($ext) ? '' : '.' . $ext);
					$n++;
				}
				while(file_exists(ds($destinationDir.'/'.$baseFilename)));
				Log::Add('INF', "New destination filename: `{$baseFilename}`.", __LINE__, __FILE__);
			}

			return $baseFilename;
		}
		else {
			if (is_dir(ds($destinationDir."/{$baseFilename}"))) {
				$n    = 1;
				$base = $baseFilename;
				do {
					$baseFilename = $base . $divider . $n;
					$n++;
				}
				while(is_dir(ds($destinationDir.'/'.$baseFilename)));
				Log::Add('INF', "New destination filename: `{$baseFilename}`.", __LINE__, __FILE__);
			}

			return $baseFilename;
		}
	}
	//-

	/**
	 * Search All Directories For Specific File Type
	 * ----
	 * @param string $directory -- absolute path
	 * @param string $fileType  -- you can use | to search for more file types example: jpg|jpeg|png|gif|bmp
	 * @param bool   $deepScan  -- will search sub directories too
	 * @param string $filter    -- (it will take filename without extention, won't apply for directories) *something | something* | *something*
	 * ----
	 * @return array
	 */
	public static function Find($directory, $fileType, $deepScan=true, $filter=false)
	{
		$directory = trim($directory); # Removed \/

		$fileTypeArr = explode('|', $fileType);

		if (!is_dir($directory)) {
			Log::Add('WAR', "Can't find files, directory doesn't exist: `{$directory}`.", __LINE__, __FILE__);
			return false;
		}

		$List = scandir($directory);
		unset($List[0], $List[1]);

		$Files = array();

		if (is_array($List) && !empty($List)) {
			foreach ($List as $item) {
				if (is_dir($directory.'/'.$item) && $deepScan) {
					$NewFiles = self::Find($directory.'/'.$item, $fileType);
					if (is_array($NewFiles)) {
						$Files = array_merge($NewFiles, $Files);
					}
				}
				else {
					if (in_array(self::Extension($item), $fileTypeArr)) {

						if ($filter) {
							if ((substr($filter,0,1) == '*') && (substr($filter,-1,1) == '*') ) {
								$type   = 'mid';
								$find = substr($filter,1,-1);
							}
							elseif (substr($filter,0,1) == '*') {
								$type = 'end';
								$find = substr($filter,1);
								$len  = strlen($find);
							}
							elseif (substr($filter,-1,1) == '*') {
								$type = 'start';
								$find = substr($filter,0,-1);
								$len  = strlen($find);
							}
							else {
								Log::Add('WAR', "Wrong filter provided: `{$filter}`.", __LINE__, __FILE__);
								return false;
							}

							$baseFileName = basename($item, '.'.self::Extension($item));

							if ( (($type == 'mid')   && (strpos($baseFileName, $find))) OR
								 (($type == 'end')   && (substr($baseFileName,-$len,$len) == $find)) OR
								 (($type == 'start') && (substr($baseFileName,0,$len) == $find)) )
							{
								$Files[] = array(
									'directory' => $directory,
									'file' => $item
								);
								continue;
							}
							else {
								continue;
							}
						}

						$ext       = self::Extension($item);
						$file_only = substr($item, 0, -strlen($ext)-1);

						$Files[] = array(
							'full'      => ds($directory.'/'.$item),
							'directory' => $directory,
							'file'      => $item,
							'file_only' => $file_only,
							'ext'       => $ext,
						);
					}
				}
			}
		}

		return $Files;
	}
	//-

	/**
	 * Will scan folder, and return array of (md5) signatures.
	 * ---
	 * @param string $directory    -- directory which you want to scan
	 * @param bool   $deepScan     -- should sub-directories be included too?
	 * @param string $subDirectory -- you can leave this as it is -- since this will be used in recursion
	 * ---
	 * @return array
	 */
	public static function GetSignatures($directory, $deepScan=true, $subDirectory=null)
	{
		$Directory = scandir($directory.'/'.$subDirectory);
		$Files = array();
		foreach ($Directory as $d) {
			if (substr($d,0,1)=='.') continue;
			if (is_dir(ds($directory.'/'.$subDirectory.'/'.$d))) {
				if (!$deepScan) continue;
				$Files = array_merge($Files, self::GetSignatures($directory, $deepScan, ds($subDirectory.'/'.$d)));
			}
			else {
				$Files[ds($subDirectory.'/'.$d)] = md5_file(ds($directory.'/'.$subDirectory.'/'.$d));
			}
		}
		return $Files;
	}
	//-

	/**
	 * Return File Extension
	 * --------
	 * @param string $fileName
	 * --------
	 * @return string
	 */
	public static function Extension($fileName)
	{
		preg_match('/\.([a-zA-Z0-9]+)$/i', $fileName, $fileExt);
		$fileExt = (isset($fileExt[1])) ? strtolower($fileExt[1]) : '';
		return $fileExt;
	}
	//-

	/**
	 * Get only filename from full path (example: /my_dir/sample/another/my_file.ext => my_file.ext | my_file)
	 * ---
	 * @param string $fullPath
	 * @param bool   $noExtension -- no ext?
	 * ---
	 * @return string
	 */
	public static function FileName($fullPath, $noExtension=false)
	{
		$name = basename($fullPath);

		if ($noExtension) {
			$ext  = self::Extension($name);
			$name = basename($name, '.'.$ext);
		}

		return $name;
	}
	//-

	/**
	 * Generates uniqute filename, you must provide full aboslute path.
	 * Note, this method doesn't check if file already exists; it just md5 the filename,
	 * and based on that make sure, that two files on filesytem can't have same filename.
	 *
	 * If you need actually unique filename (with checking of existance),
	 * then use method UniqueName()
	 * ---
	 * @param string $file
	 * ----
	 * @return string
	 */
	public static function UniqueFilename($file)
	{
		# Make Sha1
		$file_sha  = vString::Hash($file, false);

		# Get Just Base Filename, without extention!
		$file_base = basename($file);
		$file_base = vString::Clean($file_base, 100, 'a A 1');

		# Get All Directories as array, and select the one,
		# in which is file
		$File = ds($file);
		$File = explode(DIRECTORY_SEPARATOR, $file);
		if (is_array($File)) {
			$dir_before = $File[count($File)-2];
			$dir_before = vString::Clean($dir_before, 100, 'a A 1');
		}

		# Create New Filename
		$newFilename = $file_sha . '_' . $dir_before . '_' . $file_base;
		$newFilename = vString::Clean($newFilename, 0, 'a A 1 c', '_');

		return $newFilename;
	}
	//-

	/**
	 * Convert size (from bytes) to nicer (human readable) value (kb, mb)
	 * ----
	 * @param string $size (bytes)
	 * ----
	 * @return string
	 */
	public static function FormatSize($size)
	{
		if ($size < 1024) {
			return $size . ' bytes';
		}
		elseif ($size < 1048576) {
			return round($size/1024) . ' KB';
		}
		else {
			return round($size/1048576, 1) . ' MB';
		}
	}
	//-

	/**
	 * Create Directory
	 * ---
	 * @param string  $folderName  -- must be full path, + new folder's name
	 * @param bool    $recursive   --
	 * @param integer $mode        -- 0755 Read and write for owner, read for everybody else
	 * ---
	 * @return bool
	 */
	public static function MakeDir($folderName, $recursive=true, $mode = 0755)
	{
		$folderName = ds($folderName);

		if (!is_dir($folderName)) {
			$oldumask = umask(0);
			if ( mkdir($folderName, $mode, $recursive) ) {
				Log::Add('INF', "Folder: `{$folderName}` was added.", __LINE__, __FILE__);
				$return = true;
			}
			else {
				Log::Add('ERR', "Error while creating folder: `{$folderName}`.", __LINE__, __FILE__);
				$return = false;
			}
			umask($oldumask);
			return $return;
		}
		else {
			Log::Add('WAR', "Folder already exists: `{$folderName}`.", __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Create Many Directories
	 * ---
	 * @param string $root -- root dir
	 * @param array  $Directories -- array of directories to create (you can enter whole path like this: array('mydir', 'another/something_else', 'first/second/third'))
	 * @param integer $mode        -- 0755 Read and write for owner, read for everybody else
	 * ---
	 * @return integer (number of creted directories)
	 */
	public static function MakeDirTree($root, $Directories, $mode=0755)
	{
		$result = 0;

		if (!is_dir($root)) {
			Log::Add('ERR', "Root is not valid directory: `{$root}`.", __LINE__, __FILE__);
			return false;
		}

		if (is_array($Directories) and !empty($Directories)) {
			foreach ($Directories as $dir) {
				if (self::MakeDir($root.'/'.$dir, true, $mode)) {
					$result++;
				}
			}
		}
		else {
			Log::Add('ERR', "Provided list of directory was empty - or wasn't an array.", __LINE__, __FILE__);
			return false;
		}

		return $result;
	}
	//-

	/**
	 * Check If Directory Is Writable
	 * --
	 * @param string $directory
	 * ---
	 * @return bool
	 */
	public static function IsWritable($directory)
	{
		$directory = ds($directory);

		Log::Add('INF', "Check if directory is writable: `{$directory}`.", __LINE__, __FILE__);

		# Check If Provided Path Is Valid
		if (!is_dir($directory)) {
			Log::Add('WAR', "Invalid path was provided.", __LINE__, __FILE__);
			return false;
		}

		# Default function - if returns false,
		# then we know it isn't writable...
		if (!is_writable($directory)) {
			return false;
		}

		# In other case, we'll check (by trying create an directory)
		# Set Dir Name (must be unique - and shouldn't exists)
		do {
			$dir = ds($directory.'/___write_test_dir_avrelia_'.rand(0,200).time());
		}
		while(!is_dir($directory));

		# Now, try to create it, check if exists, delete it and check if doesn't exists (anymore) - if that goes fine - we'll return true...
		if (@mkdir($dir)):
			if (is_dir($dir)):
				if (@rmdir($dir)):
					if (!is_dir($dir)) return true;
				endif;
			endif;
		endif;
		# Funny --- -----------

		return false;
	}
	//-

	/**
	 * Select file or folder (return object, with file / folder related methods)
	 * ---
	 * @param string $path
	 * ---
	 * @return FileSystemSelect
	 */
	public static function Select($path)
	{
		return new FileSystemSelect($path);
	}
	//-
}
//--

class FileSystemSelect
{
	# Selection path
	private $path;

	# Selection type (dir or file)
	private $type;

	/**
	 * Make new selection
	 * ---
	 * @param string $path
	 * ---
	 * @return void
	 */
	public function __construct($path)
	{
		if (!file_exists($path)) {
			Log::Add('WAR', "Selecting directory or file which doesn't exists!", __LINE__, __FILE__);
			return false;
		}
		else {
			Log::Add('INF', "Making new selection: `{$path}`.", __LINE__, __FILE__);
			$this->path = $path;
			$this->type = is_dir($path) ? 'dir' : 'file';
		}
	}
	//-

	/**
	 * Enter only filename (not full path)
	 * ---
	 * @param string $newFilename
	 * @param bool   $autoOnExist   -- will autoname if file already exist
	 * @param bool   $keepExtention -- will keep extention, for example:
	 * 		if old filename is: cat.txt, and for new filename you enter kitty,
	 * 		the result will be kitty.txt, if you have $keepExtention set to true.
	 * ---
	 * @return boolean (new filename (with full path) or false)
	 */
	public function rename($newFilename, $autoOnExist=true, $keepExtention=true)
	{
		if (!$this->path) { return false; }

		$pathOnly = dirname($this->path);
		$fnOnly   = basename($this->path);
		$ext      = FileSystem::Extension($fnOnly);

		# Keep extention?
		if ($keepExtention && $this->type == 'file') {
			if ('.'.$ext != substr($newFilename,-strlen($ext)-1)) {
				$newFilename = $newFilename . '.' . $ext;
			}
		}

		# File exists?
		if (file_exists(ds($pathOnly.'/'.$newFilename)))
		{
			if ($autoOnExist) {
				$newFilename = FileSystem::UniqueName($newFilename, $pathOnly, ($this->type == 'dir' ? false : true));
			}
			else {
				Log::Add('WAR', "File aready exists: `" . ds($pathOnly.'/'.$newFilename) . '`.', __LINE__, __FILE__);
				return false;
			}
		}

		if (FileSystem::Rename($this->path, ds($pathOnly . '/' . $newFilename))) {
			$this->path = ds($pathOnly . '/' . $newFilename);
			return $this->path;
		}
		else {
			return false;
		}
	}
	//-

	/**
	 * Will copy folder to new destination
	 * ---
	 * @param string $destination -- full absolute path, or use './new_folder' for relative path
	 * @param bool   $selectCopy  -- will select copy, if set to true, or
	 * 				keer current selection if set to false.
	 * ---
	 * @return boolean (new filename (with full path) or false)
	 */
	public function copy($destination, $selectCopy=false)
	{
		# Relative path?
		if (substr($destination,0,2) == './') {
			$destination = ds(dirname($this->path) . '/' . substr($destination,2));
		}

		# Copy of directory?
		if ($this->type == 'dir') {
			$destination = ds($destination.'/'.basename($this->path));
		}

		# Copy finally
		if (FileSystem::Copy($this->path, $destination, false, false)) {
			if ($selectCopy) {
				$this->path = realpath($this->type == 'dir' ? $destination : ds($destination.'/'.basename($this->path)));
			}
			return true;
		}
	}
	//-
}
//--
