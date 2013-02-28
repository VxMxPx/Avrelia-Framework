<?php

namespace Plug\Avrelia;

use Avrelia\Core\Cfg        as Cfg;
use Avrelia\Core\Str        as Str;
use Avrelia\Core\Arr        as Arr;
use Avrelia\Core\vString    as vString;
use Avrelia\Core\Log        as Log;
use Avrelia\Core\Plug       as Plug;
use Avrelia\Core\Event      as Event;

/**
 * Session Class
 * -----------------------------------------------------------------------------
 * Session Plug, Main Class
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class Session
{
    /**
     * Current user object
     * @var array
     */
    protected static $current = false;


    /**
     * Load config and discover session.
     * --
     * @return  boolean
     */
    public static function _on_include_()
    {
        Plug::get_config(__FILE__);
        self::_session_discover();

        return true;
    }

    /**
     * Enable plug
     * --
     * @return  boolean
     */
    public static function _on_enable_()
    {
        Plug::get_config(__FILE__);

        // Needs database to function
        Plug::need(array('Plug\\Avrelia\\Database'));

        // Do not create things on enable...
        if (Cfg::get('plugs/session/create_on_enable', false) === false) { return true; }

        # Create Sessions table (if doesn't exists)
        foreach (array('sessions_table') as $table) {

            $table_sql = Cfg::get('plugs/session/tables/'.$table);

            if ($table_sql) {

                $table_sql = str_replace(
                    '{{table_name}}',
                    Cfg::get('plugs/session/'.$table),
                    $table_sql);

                Database::execute($table_sql);
            }
        }

        return true;
    }

    /**
     * Disable plug
     * --
     * @return  boolean
     */
    public static function _on_disable_()
    {
        Plug::get_config(__FILE__);

        // Do not drop it when disabled!
        if (Cfg::get('plugs/session/drop_on_disable', false) === false) {

            return true;
        }

        if (Cfg::get('plugs/session/tables/sessions_table')) {
            Database::execute('DROP TABLE IF EXISTS ' . Cfg::get('plugs/session/sessions_table'));
        }

        return true;
    }

    /**
     * Return current user's object or false.
     * --
     * @return object or false
     */
    public static function current()
    {
        return self::$current;
    }

    /**
     * Will process (clean) user's agent.
     * --
     * @param   string  $agent
     * --
     * @return  string
     */
    protected static function _clean_agent($agent)
        { return Str::clean(str_replace(' ', '_', $agent), 'aA1', '_'); }

    /**
     * Get current user's ID
     * --
     * @return mixed
     */
    protected static function _current_id()
    {
        $id = Cfg::get('plugs/session/user_id');
        return self::$current->{$id};
    }

    /**
     * List all sessions currently set.
     * --
     * @return array
     */
    public static function list_all()
    {
        # Look for session
        $sessions = Database::find(Cfg::get('plugs/session/sessions_table'));

        # Okey we have something, check it...
        if ($sessions->succeed())
            { return $sessions->as_array(); }
        else
            { return array(); }
    }

    /**
     * Clear all sessions.
     * --
     * @return void
     */
    public static function clear_all()
    {
        return Database::truncate(Cfg::get('plugs/session/sessions_table'));
    }

    /**
     * Will create session for particular user
     * --
     * @param   integer $id
     * @param   boolean $expires
     * --
     * @return  boolean
     */
    public static function create($id, $expires=null)
    {
        if (self::_user_set($id)) {
            return self::_session_set(self::_current_id(), $expires);
        }
    }

    /**
     * Will destroy current session
     * --
     * @return  void
     */
    public static function destroy()
    {
        if (self::$current) {
            self::_session_destroy(self::_current_id());
            self::$current = false;
        }
    }

    /**
     * Is user logged in?
     * --
     * @return  boolean
     */
    public static function has()
        { return !!self::$current; }

    /**
     * Will reload current session.
     * Useful after updating user's informations.
     * --
     * @return  void
     */
    public static function reload()
        { self::has() and self::_user_set(self::_current_id()); }

    /**
     * Will clear all expired sessions.
     * --
     * @return  void
     */
    public static function cleanup()
    {
        Database::delete(
            Cfg::get('plugs/session/sessions_table'),
            'WHERE expires_on < :expires',
            array('expires' => time())
        );
    }

    /**
     * Check if user can be found and set info.
     * --
     * @param   integer  $user
     * --
     * @return  boolean
     */
    protected static function _user_set($id)
    {
        # User's model
        $method = Cfg::get('plugs/session/user_method');
        $method = explode('::', $method, 2);
        $model  = $method[0];
        $method = $method[1];

        $user = call_user_func_array(array($model, $method), array($id));

        Event::trigger('/plugs/avrelia/session/user_set', $user);

        self::$current = $user;
        return !!self::$current;
    }

    /**
     * Will seek for user's session!
     * If one is found, the user will be auto-logged in, and true for this function
     * will be returned, else false will be returned.
     * --
     * @return  boolean
     */
    protected static function _session_discover()
    {
        # Check if we can find session id in cookies.
        if (false === ($session_id = Cookie::read(Cfg::get('plugs/session/cookie_name')))) {
            Log::inf("No session found.");
            return false;
        }

        # Look for session
        $session_details = Database::find(
                                Cfg::get('plugs/session/sessions_table'),
                                array('id' => Str::clean($session_id, 'aA1', '_', 400))
                            );

        # Okey we have something, check it...
        if ($session_details->failed()) {

            Log::inf("Session found in cookies but not in database: `{$session_id}`.");
            return false;
        }

        $session_details  = $session_details->as_array(0);
        $user_id          = $session_details['user_id'];
        $expires          = $session_details['expires_on'];
        $ip               = $session_details['ip'];
        $agent            = $session_details['agent'];
        $expiration_value = $session_details['expiration_value'];

        # Check if is expired?
        if ($expires < time()) {
            Log::inf("Session was found, but it's expired.");
            self::_session_destroy($session_id);

            return false;
        }

        # Do we have to match IP address?
        if (Cfg::get('plugs/session/require_ip')) {
            if ($ip !== $_SERVER['REMOTE_ADDR']) {
                Log::inf("The IP from session file: `{$ip}`, " .
                         "doesn't match with actual IP: `{$_SERVER['REMOTE_ADDR']}`.");
                self::_session_destroy($session_id);

                return false;
            }
        }

        # Do we have to match agent?
        if (Cfg::get('plugs/session/require_agent')) {
            $current_agent = self::_clean_agent($_SERVER['HTTP_USER_AGENT']);

            if ($agent !== $current_agent) {
                Log::inf("The agent from session file: `{$agent}`, " .
                         "doesn't match with actual agent: `{$current_agent}`.");
                self::_session_destroy($session_id);

                return false;
            }
        }

        # Try to set user now...
        if (self::_user_set($user_id)) {
            self::_session_update($user_id, $session_id, (int) $expiration_value);
            return true;
        }
    }

    /**
     * Will update current session (extend it!)
     * --
     * @param  string  $user_id
     * @param  string  $session_id
     * @param  integer $expires
     * --
     * @return void
     */
    protected static function _session_update($user_id, $session_id, $expires)
    {
        Database::update(
            array('expires_on' => time() + (!$expires ? 60 * 60 : $expires)),
            Cfg::get('plugs/session/sessions_table'),
            array('user_id' => $user_id));

        // If no expiration value was set, that means we have session which
        // must expire on browser close, therefore, we won't extend cookie,
        // but we will extend database record!
        if ($expires) {
            Cookie::create(
                Cfg::get('plugs/session/cookie_name'),
                $session_id,
                $expires + time());
        }
    }

    /**
     * Set session (set cookie, add info to sessions file)
     * --
     * @param   string  $user_id
     * @param   integer $expires Null for default or costume expiration in seconds,
     *                           0, to expires when browser is closed.
     * --
     * @return  boolean
     */
    protected static function _session_set($user_id, $expires=null)
    {
        // Save expiration value
        $expiration_value = $expires === null
            ? (int) Cfg::get('plugs/session/expires')
            : (int) $expires;

        # Set expires to some time in future. It 0 was set in config, then we
        # set it to expires imidietly when browser window is closed.
        if ($expires === null) {
            $expires = (int) Cfg::get('plugs/session/expires');
            $expires = $expires > 0 ? $expires + time() : 0;
        }
        else {
            $expires = (int) $expires;
        }

        # Create unique id
        $q_id  = time() . '_' . Str::random(20, 'aA1');

        # Store cookie
        Cookie::create(Cfg::get('plugs/session/cookie_name'), $q_id, $expires);

        # Set session file
        $session = array(
            'id'               => $q_id,
            'user_id'          => $user_id,
            'expires_on'       => $expires === 0 ? time() + 60 * 60 : $expires,
            'ip'               => $_SERVER['REMOTE_ADDR'],
            'agent'            => self::_clean_agent($_SERVER['HTTP_USER_AGENT']),
            'expiration_value' => $expiration_value
        );

        return Database::create(
                $session,
                Cfg::get('plugs/session/sessions_table'))->succeed();
    }

    /**
     * Used mostly on logout, will remove session's cookies and unset it in file.
     * --
     * @param   string  $sessionId
     * --
     * @return  boolean
     */
    protected static function _session_destroy($user_id)
    {
        # Remove cookies
        Cookie::remove(Cfg::get('plugs/session/cookie_name'));

        # Cleanup
        self::cleanup();

        # Okay, clear session now...
        return Database::delete(
                Cfg::get('plugs/session/sessions_table'),
                array('user_id' => (int) $user_id))->succeed();
    }

}