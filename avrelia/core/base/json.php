<?php namespace Avrelia\Core; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Json Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Json
{
    /**
     * Decode a JSON file, and return it as Array or Object
     * 
     * @param  string   $filename -- The file with JSON string
     * @param  bool     $assoc    -- When TRUE, returned object will be 
     *                               converted into associative array.
     * @param  integet  $depth    -- User specified recursion depth.
     * @return mixed
     */
    public static function decode_file($filename, $assoc=false, $depth=512)
    {
        $filename = ds($filename);

        if (file_exists($filename)) {
            $content = FileSystem::Read($filename);
            return self::decode($content, $assoc, $depth);
        }
        else 
            { return Log::war("File not found: `{$filename}`."); }
    }

    /**
     * Decode a JSON string, and return it as Array or Object
     * 
     * @param string   $json     -- The json string being decoded.
     * @param bool     $assoc    -- When TRUE, returned object will be converted 
     *                              into associative array.
     * @param integet  $depth    -- User specified recursion depth.
     * @return mixed
     */
    public static function decode($json, $assoc=false, $depth=512)
    {
        $decoded = json_decode($json, $assoc, $depth);

        if (json_last_error() != JSON_ERROR_NONE) {
            $JSONErrors = array(
                JSON_ERROR_NONE      => 'No error has occurred',
                JSON_ERROR_DEPTH     => 'The maximum stack depth has been exceeded',
                JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
                JSON_ERROR_SYNTAX    => 'Syntax error',
            );
            Log::err("JSON decode error: `" . $JSONErrors[json_last_error()] . '`.');
            return false;
        }
        else {
            return $decoded;
        }
    }

    /**
     * Save the JSON representation of a value, to the file.
     * If file exists, it will be overwritten.
     * 
     * @param string $filename -- The file to which the data will be saved.
     * @param mixed  $values   -- The value being encoded. Can be any type 
     *                            except a resource . This function only works 
     *                            with UTF-8 encoded data.
     * @param int    $options  -- Bitmask consisting of JSON_HEX_QUOT, 
     *                            JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, 
     *                            JSON_FORCE_OBJECT.
     * @return bool
     */
    public static function encode_file($filename, $values, $options=0)
    {
        return FileSystem::Write(
                    self::encode($values, $options), 
                    $filename, 
                    false
                );
    }

    /**
     * Returns the JSON representation of a value
     * 
     * @param mixed  $values   -- The value being encoded. Can be any type 
     *                            except a resource . This function only works 
     *                            with UTF-8 encoded data.
     * @param int    $options  -- Bitmask consisting of JSON_HEX_QUOT, 
     *                            JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, 
     *                            JSON_FORCE_OBJECT.
     * @return string
     */
    public static function encode($values, $options=0)
    {
        return json_encode($values, $options);
    }

    /**
     * JSON response
     * 
     * @param   mixed   $values  The value being encoded. Can be any type except 
     *                           a resource . This function only works with 
     *                           UTF-8 encoded data.
     * @param   boolean $die
     * @param   integer $options Bitmask consisting of JSON_HEX_QUOT, 
     *                           JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS, 
     *                           JSON_FORCE_OBJECT.
     * @param   void
     */
    public static function response($values, $die=false, $options=0)
    {
        header("Content-type: application/json");

        $message = self::encode($values, $options);

        if ($die) 
            { die($message); }
        else 
            { Output::add($message, 'AvreliaHTTP.JsonResponse'); }
    }
}
