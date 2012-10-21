<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Message Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Message
{
    /**
     * @var array  The list of all messages
     */
    private static $list = array();

    /**
     * Add a Message To The List.
     * If you added OK or INF true will be returned else false.
     * --
     * @param   string  $message
     * @param   string  $type       inf|war|err|ok == information, warning, 
     *                              error, successfully done
     * @param   string  $group      Any particular group?
     * --
     * @return  boolean
     */
    public static function add($message, $type, $group=null)
    {
        $type = strtolower($type);

        self::$list[] = array
        (
            'type'      => $type,
            'message'   => $message,
            'group'     => $group,
        );

        return ($type === 'ok' || $type === 'inf')
                ? true
                : false;
    }

    /**
     * Shortcuts for add method.
     * --
     * @param  string $message
     * --
     * @return boolean
     */
    public static function inf($message, $group=null)
        { return self::add($message, 'inf', $group); }
    public static function ok($message, $group=null)
        { return self::add($message, 'ok', $group); }
    public static function err($message, $group=null)
        { return self::add($message, 'err', $group); }
    public static function war($message, $group=null)
        { return self::add($message, 'war', $group); }

    /**
     * Add a message to the list AND to log.
     * $file will be used as a group.
     * If you added OK or INF true will be returned else false.
     * --
     * @param   string  $type       inf|war|err|ok == information, warning, 
     *                              error, successfully done
     * @param   string  $message
     * @param   string  $group      Any particular group?
     * --
     * @return  boolean
     */
    public static function log($message, $type, $group=null)
    {
        $type = strtolower($type);

        self::add($message, $type, $group);
        $type = $type === 'ok' ? 'inf' : $type;
        return Log::add($message, $type);
    }

    /**
     * Return or echo all messages
     * --
     * @param   string  $group  Any particular group?
     * --
     * @return  string
     */
    public static function as_html($group=null)
    {
        if (!is_array(self::$list)) { return false; }

        $return = '';

        $inf = $war = $err = $ok = array();

        foreach (self::$list as $message)
        {
            if ($group != null && $message['group'] != $group) continue;
            if ($message['type']  == 'ERR')  $err[] = $message;
            if ($message['type']  == 'WAR')  $war[] = $message;
            if ($message['type']  == 'INF')  $inf[] = $message;
            if ($message['type']  == 'OK')   $ok[]  = $message;
        }

        # Get Errors
        if (!empty($err)) {
            $return .= '<div id="tERR" class="mItem"><div class="mIco"><span>ERR:</span></div><div class="mMsg">';
            foreach ($err as $message) {
                $return .= '<div>'.$message['message'].'</div>'."\n";
            }
            $return .= '</div></div>'."\n";
        }

        # Get Warnings
        if (!empty($war)) {
            $return .= '<div id="tWAR" class="mItem"><div class="mIco"><span>WAR:</span></div><div class="mMsg">';
            foreach ($war as $message) {
                $return .= '<div>'.$message['message'].'</div>'."\n";
            }
            $return .= '</div></div>'."\n";
        }

        # Get Infos
        if (!empty($inf)) {
            $return .= '<div id="tINF" class="mItem"><div class="mIco"><span>INF:</span></div><div class="mMsg">';
            foreach ($inf as $message) {
                $return .= '<div>'.$message['message'].'</div>'."\n";
            }
            $return .= '</div></div>'."\n";
        }

        # Get Ok
        if (!empty($ok)) {
            $return .= '<div id="tOK" class="mItem"><div class="mIco"><span>OK:</span></div><div class="mMsg">';
            foreach ($ok as $message) {
                $return .= '<div>'.$message['message'].'</div>'."\n";
            }
            $return .= '</div></div>'."\n";
        }

        return $return;
    }

    /**
     * Return plain (array) list of messages
     * --
     * @param   boolean $plain  If true, you'll get regular array instead of 
     *                          associative array.
     * --
     * @return  array
     */
    public static function as_array($plain=false)
    {
        if (empty(self::$list)) {
            return array();
        }

        $list = array();

        if ($plain) {
            foreach (self::$list as $item) {
                $list[] = array($item['type'], $item['message'], $item['group']);
            }
        }
        else {
            $list = self::$list;
        }

        return $list;
    }

    /**
     * Set Messages List (from array)
     * --
     * @param   array   $messages   List of messages
     * @param   boolean $merge      Merge list with existing?
     * --
     * @return  void
     */
    public static function add_array($messages, $merge=false)
    {
        if (!is_array($messages)) return false;

        if ($merge) {
            self::$list = array_merge(self::$list, $messages);
        }
        else {
            self::$list = $messages;
        }
    }

    /**
     * Check if there is any message (of particular type)
     * --
     * @param   string  $type
     * @param   string  $group  For any particular group?
     * --
     * @return  boolean
     */
    public static function has($type=false, $group=null)
    {
        if ($type) {
            if (self::has()) {
                foreach (self::$list as $key => $messages) {
                    if ($group == null || $messages['group'] == $group) {
                        if ($messages['type'] == $type) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }

        return (is_array(self::$list)) && (!empty(self::$list))
                ? true
                : false;
    }
}
