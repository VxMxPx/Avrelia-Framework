<?php

namespace Plug\Avrelia;

use Avrelia\Core\FileSystem as FileSystem;
use Avrelia\Core\Log        as Log;

/**
 * XML Plug
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class XML
{
    # XML Resource, as string
    private $resource     = false;

    # XML as object
    private $resource_xml = false;

    /**
     * Load xml from file, or, if you pass in string, from string.
     * If you pass in file, plase prefix it with "file://"!!
     * --
     * @param  string $source
     * --
     * @return void
     */
    public function __construct($source)
    {
        if (substr($source, 0, 7) === 'file://') {
            if (file_exists($source)) {
                $source = FileSystem::Read($source);
            }
            else {
                Log::err("File not found: `{$source}`.");
                return false;
            }
        }

        if (empty($source)) {
            Log::war("Empty source file send in!");
            return false;
        }

        $this->resource = $source;
        $this->reload();
        return true;
    }

    /**
     * Reload, xml object
     * --
     * @return true
     */
    private function reload()
    {
        $this->resource_xml = new SimpleXMLElement($this->resource);
        return true;
    }

    /**
     * Pass in XML string, and namespaces ":" will be converted to "_", if you
     * wanna then convert that XML to Array, namespaces won't cause problems.
     * Namespaces are silly little thing, mostly causing problems,
     * insteead of solving them.
     * --
     * @return void
     */
    public function kill_ns()
    {
        $this->resource = preg_replace(
                            '/(<\/?[a-zA-Z0-9\-_]*?):([a-zA-Z0-9\-_]*?[ >])/',
                            '$1_$2',
                            $this->resource);
        $this->reload();
    }

    /**
     * Select nodes by XPath
     * --
     * @param  string $path
     * --
     * @return array
     */
    public function xpath($path)
    {
        return $this->resource_xml->xpath($path);
    }

    /**
     * Load xml from file, or, if you pass in string, from string.
     * If you pass in file, plase prefix it with "file://"!!
     * This is the same as calling new Avrelia\XML($source)
     * --
     * @param  string $source
     * --
     * @return self   instance of self
     */
    public static function get($source)
    {
        return new self($source);
    }

    /**
     * Convert an Array to XML
     * --
     * @param array  $input
     * @param string $parent  If key is numberic, can be named as $parent
     * --
     * @return string         (XML)
     */
    public static function from_array(array $input, $parent=null)
    {
        $XML = '';

        foreach ($input as $key => $val) {
            if (is_numeric($key) && $parent) {
                $key = $parent;
            }

            if (is_array($val)) {
                // In this case we won't use parent...
                if (isset($val[0])) {
                    $XML .= self::from_array($val, $key);
                }
                else {
                    $XML .= "<{$key}>\n" . self::from_array($val, null) . "</{$key}>\n";
                }
            }
            else {
                $XML .= "<{$key}>{$val}</{$key}>\n";
            }
        }

        return $XML;
    }
}