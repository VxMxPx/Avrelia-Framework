<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Cfg        as Cfg;
use Avrelia\Core\Str        as Str;
use Avrelia\Core\vString    as vString;
use Avrelia\Core\Log        as Log;

/**
 * Session Driver Db Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class SessionDriverDb implements SessionDriverInterface
{
    /**
     * Current's user data
     * @var array
     */
    private $current = false;


    /**
     * Will construct the database object.
     * --
     * @return void
     */
    public function __construct()
    {
        # Try to find sessions
        $this->_session_discover();
    }

    /**
     * Create all files / tables required by this plug to work
     * --
     * @return  boolean
     */
    public static function _on_enable_()
    {
        // Do not create things on enable...
        if (Cfg::get('plugs/session/create_on_enable', false) === false) { return true; }

        # Create users table (if doesn't exists)
        if (Plug::has('database')) {
            Cfg::get('plugs/session/db/tables/users_table') 
                and Database::execute(Cfg::get('plugs/session/db/tables/users_table'));

            Cfg::get('plugs/session/db/tables/sessions_table') 
                and Database::execute(Cfg::get('plugs/session/db/tables/sessions_table'));
        }
        else {
            trigger_error(
                "Can't create, database plug must be enabled.", 
                E_USER_ERROR);

            return false;
        }

        $defaults = Cfg::get('plugs/session/defaults');

        foreach ($defaults as $default_user)
        {
            $default_user['password'] = vString::Hash($default_user['password'], false, true);
            Database::create($default_user, Cfg::get('plugs/session/users_table'));
        }

        return true;
    }

    /**
     * Destroy all elements created by this plug
     * --
     * @return  boolean
     */
    public static function _on_disable_()
    {
        // Do not drop it when disabled!
        if (Cfg::get('plugs/session/drop_on_disable', false) === false) { return true; }

        Cfg::get('plugs/session/db/tables/users_table') 
            and Database::execute('DROP TABLE IF EXISTS ' . Cfg::get('plugs/session/users_table'));

        Cfg::get('plugs/session/db/tables/sessions_table') 
            and Database::execute('DROP TABLE IF EXISTS ' . Cfg::get('plugs/session/sessions_table'));

        return true;
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
     * Return user's information as an array.
     * --
     * @return  array
     */
    public function as_array()
        { return $this->current; }

    /**
     * Get particular information about user (session).
     * --
     * @param  string  $key
     * @param  mixed   $default
     * --
     * @return mixed
     */
    public function get($key, $default=false)
        { return Arr::element($key, $this->current, $default); }

    /**
     * List all sessions currently set.
     * --
     * @return array
     */
    public function list_all()
    {
        # Look for session
        $sessions = Database::find(Cfg::get('plugs/session/db/sessions_table'));

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
    public function clear_all()
    {
        return Database::truncate(Cfg::get('plugs/session/db/sessions_table'));
    }

    /**
     * Will create session for particular user
     * --
     * @param   integer $id
     * @param   boolean $expires
     * --
     * @return  boolean
     */
    public function create($id, $expires=null)
    {
        if ($this->_user_set($id)) {
            return $this->_session_set($this->current['id'], $expires);
        }
    }

    /**
     * Will destroy current session
     * --
     * @return  void
     */
    public function destroy()
    {
        if ($this->current) {
            $this->_session_destroy($this->current['id']);
            $this->current = false;
        }
    }

    /**
     * Is user logged in?
     * --
     * @return  boolean
     */
    public function has()
        { return !!$this->current; }

    /**
     * Will reload current session.
     * Useful after updating user's informations.
     * --
     * @return  void
     */
    public function reload()
        { $this->has() and $this->_user_set($this->current['id']); }

    /**
     * Will clear all expired sessions.
     * --
     * @return  void
     */
    public function cleanup()
    {
        Database::delete(
            Cfg::get('plugs/session/db/sessions_table'),
            'WHERE expires < :expires',
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
    private function _user_set($id)
    {
        # Select user
        $user = Database::find(
                    Cfg::get('plugs/session/db/users_table'), 
                    array('id' => (int)$id));

        # Valid user?
        if ($user->failed()) {
            Log::inf("Invalid id, user not found: `{$id}`.");
            return false;
        }

        $user = $user->as_array(0);

        if (Cfg::get('plugs/session/require_active', true) && !$user['active']) {
            Log::inf("User's account is not active, can't continue.");
            return false;
        }

        Log::inf("User found by id: `{$user['id']}`.");
        $this->current = $user;

        return true;
    }

    /**
     * Will seek for user's session!
     * If one is found, the user will be auto-logged in, and true for this function
     * will be returned, else false will be returned.
     * --
     * @return  boolean
     */
    private function _session_discover()
    {
        # Check if we can find session id in cookies.
        if ($session_id = Cookie::read(Cfg::get('plugs/session/cookie_name')))
        {
            # Look for session
            $session_details = Database::find(
                                    Cfg::get('plugs/session/db/sessions_table'),
                                    array('id' => Str::clean($session_id, 'aA1', '_', 400))
                                );

            # Okey we have something, check it...
            if ($session_details->succeed())
            {
                $session_details = $session_details->as_array(0);
                $user_id  = $session_details['user_id'];
                $expires  = $session_details['expires'];
                $ip       = $session_details['ip'];
                $agent    = $session_details['agent'];

                # Check if is expired?
                if ($expires < time()) {
                    Log::inf("Session was found, but it's expired.");
                    $this->_session_destroy($session_id);
                    return false;
                }

                # Do we have to match IP address?
                if (Cfg::get('plugs/session/require_ip')) {
                    if ($ip !== $_SERVER['REMOTE_ADDR']) {
                        Log::inf("The IP from session file: `{$ip}`, doesn't match with actual IP: `{$_SERVER['REMOTE_ADDR']}`.");
                        $this->_session_destroy($session_id);
                        return false;
                    }
                }

                # Do we have to match agent?
                if (Cfg::get('plugs/session/require_agent')) {
                    $current_agent = self::_clean_agent($_SERVER['HTTP_USER_AGENT']);

                    if ($agent !== $current_agent) {
                        Log::inf("The agent from session file: `{$agent}`, doesn't match with actual agent: `{$current_agent}`.");
                        $this->_session_destroy($session_id);
                        return false;
                    }
                }

                # Try to set user now...
                if (!$this->_user_set($user_id)) {
                    return false;
                }

                return true;
            }
            else {
                Log::inf("Session found in cookies but not in database: `{$session_id}`.");
            }
        }
        else {
            Log::inf("No session found.");
        }
    }

    /**
     * Set session (set cookie, add info to sessions file)
     * --
     * @param   string  $user_id
     * @param   boolean $expires Null for default or costume expiration in seconds,
     *                           0, to expires when browser is closed.
     * --
     * @return  boolean
     */
    private function _session_set($user_id, $expires=null)
    {
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
            'id'      => $q_id,
            'user_id' => $user_id,
            'expires' => $expires === 0 ? time() + 60 * 60 : $expires,
            'ip'      => $_SERVER['REMOTE_ADDR'],
            'agent'   => self::_clean_agent($_SERVER['HTTP_USER_AGENT']),
        );

        return Database::create(
                $session, 
                Cfg::get('plugs/session/db/sessions_table'))->succeed();
    }

    /**
     * Used mostly on logout, will remove session's cookies and unset it in file.
     * --
     * @param   string  $sessionId
     * --
     * @return  boolean
     */
    private function _session_destroy($user_id)
    {
        # Remove cookies
        Cookie::remove(Cfg::get('plugs/session/cookie_name'));

        # Cleanup
        $this->cleanup();

        # Okay, clear session now...
        return Database::delete(
                Cfg::get('plugs/session/db/sessions_table'), 
                array('user_id' => (int) $user_id))->succeed();
    }

}