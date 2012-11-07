<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\FileSystem as FileSystem;
use Avrelia\Core\Json       as Json;
use Avrelia\Core\Cfg        as Cfg;
use Avrelia\Core\Str        as Str;
use Avrelia\Core\vString    as vString;
use Avrelia\Core\Log        as Log;

/**
 * Session Driver Json Class
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class SessionDriverJson implements SessionDriverInterface
{
    /**
     * Full path to the users file.
     * @var string
     */
    protected $file_users;

    /**
     * Full path to the sessions file.
     * @var [type]string
     */
    protected $file_sessions;

    /**
     * All users.
     * @var array
     */
    protected $data_users;

    /**
     * All sessions.
     * @var array
     */
    protected $data_sessions;

    /**
     * Current user's data
     * @var array
     */
    protected $current_user = false;

    /**
     * Current session's data
     * @var array
     */
    protected $current_session = false;


    /**
     * Initialize the session - setup everything, read the cookies, etc...
     * --
     * @return  void
     */
    public function __construct()
    {
        # Set data filenames
        $this->file_users    = Cfg::get('plugs/session/json/users_filename');
        $this->file_sessions = Cfg::get('plugs/session/json/sessions_filename');

        # Load Users And Sessions
        $this->_users_fetch();
        $this->_sessions_fetch();

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

        FileSystem::Write(Json::encode(array()), Cfg::get('plugs/session/json/users_filename'),    false, 0777);
        FileSystem::Write(Json::encode(array()), Cfg::get('plugs/session/json/sessions_filename'), false, 0777);

        # Default users
        $users = array();

        $defaults = Cfg::get('plugs/session/defaults');

        foreach ($defaults as $default_user)
        {
            $user['id']        = self::_uname_to_id($default_user['uname']);
            $user['uname']     = $default_user['uname'];
            $user['password']  = vString::Hash($default_user['password'], false, true);
            $user['is_active'] = true;

            $users[$user['id']] = $user;
        }

        return Json::encode_file(Cfg::get('plugs/session/json/users_filename'), $users);
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

        $r1 = FileSystem::Remove(Cfg::get('plugs/session/json/users_filename'));
        $r2 = FileSystem::Remove(Cfg::get('plugs/session/json/sessions_filename'));

        return $r1 && $r2;
    }


    /**
     * Converts username to id.
     * --
     * @param   string  $username
     * --
     * @return  string
     */
    protected static function _uname_to_id($username)
    {
        return Str::clean(
                    Str::symbols_to_words($username), 
                    'aA1');
    }

    /**
     * Will process (clean) user's agent.
     * --
     * @param   string  $agent
     * --
     * @return  string
     */
    protected static function _clean_agent($agent)
    {
        return Str::clean(
                    str_replace(' ', '_', $agent), 
                    'aA1', 
                    '_');
    }

    /**
     * List all sessions currently set.
     * --
     * @return array
     */
    public function list_all()
        { return $this->data_sessions; }

    /**
     * Clear all sessions.
     * --
     * @return void
     */
    public function clear_all()
    {
        $this->destroy();
        $this->data_sessions = array();
        return $this->_session_write();
    }

    /**
     * Will log-in user based on id.
     * --
     * @param   integer $id
     * @param   boolean $expires
     * --
     * @return  boolean
     */
    public function create($id, $expires=null)
    {
        $user = $this->_is_valid_user($id);

        # Do we have valid user?
        if (!$user) { return false; }

        # Okay, set session and current user
        $this->_user_set($user['id']);
        $this->_session_set($user['id'], $expires);

        return true;
    }

    /**
     * Will logout current user
     * ---
     * @return  void
     */
    public function destroy()
    {
        if ($this->current_user) {
            $this->_session_destroy($this->current_session['id']);
            $this->current_session = false;
            $this->current_user    = false;
        }
    }

    /**
     * Is user logged in?
     * ---
     * @return  boolean
     */
    public function has()
        { return $this->current_session && $this->current_user; }


    /**
     * Will reload current session.
     * Useful after updating user's informations.
     * --
     * @return  void
     */
    public function reload()
    {
        if ($this->has()) {
            $this->_users_fetch();
            $this->_user_set($this->current_user['id']);
        }
    }

    /**
     * Will clear all expired sessions, and return the amount of removed items.
     * --
     * @return  integer
     */
    public function cleanup()
    {
        $removed = 0;

        foreach ($this->data_sessions as $id => $session)
        {
            if ($session['expires_on'] < time()) {
                unset($this->data_sessions[$id]);
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->_session_write();
        }

        return $removed;
    }

    /**
     * Return user's information as an array.
     * --
     * @return  array
     */
    public function as_array()
        { return $this->current_user; }

    /**
     * Get particular information about user (session).
     * --
     * @param  string  $key
     * @param  mixed   $default
     * --
     * @return mixed
     */
    public function get($key, $default=false)
        { return Arr::element($key, $this->current_user, $default); }

    /**
     * Set user (set all user's data)
     * --
     * @param   string  $user_id
     * --
     * @return  boolean
     */
    protected function _user_set($user_id)
    {
        if (!isset($this->data_users[$user_id])) {
            Log::war("Can't set user, not found: `{$user_id}`.");
            return false;
        }

        $this->current_user = $this->data_users[$user_id];
        return true;
    }

    /**
     * Check if user can be found, array or false will be returned.
     * --
     * @param   string  $user_id
     * --
     * @return  mixed
     */
    protected function _is_valid_user($user_id)
    {
        # Can we find this user?
        if (!isset($this->data_users[$user_id])) {
            Log::inf("User with this ID was not found: `{$id}`");
            return false;
        }

        # Is active?
        if (Cfg::get('plugs/session/require_active', true)) {
            if (!isset($this->data_users[$user_id]['is_active']) || $this->data_users[$user_id]['is_active'] !== true) {
                Log::inf("User's account isn't active: `{$user_id}`.");
                return false;
            }
        }

        return $this->data_users[$user_id];
    }

    /**
     * Will fetch all users (return true if successful and false if not)
     * --
     * @return  boolean
     */
    protected function _users_fetch()
    {
        if (file_exists($this->file_users)) {
            $this->data_users = Json::decode_file($this->file_users, true);
            if (is_array($this->data_users) && !empty($this->data_users)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Will seek for user's session!
     * If one is found, the user will be auto-logged in, and true for this function
     * will be returned, else false will be returned.
     * --
     * @return  boolean
     */
    protected function _session_discover()
    {
        # Check if we can find session id in cookies.
        if ($session_id = Cookie::read(Cfg::get('plugs/session/cookie_name')))
        {
            # Okey we have something, check it...
            if (isset($this->data_sessions[$session_id]))
            {
                $session_details = $this->data_sessions[$session_id];
                $user_id  = $session_details['user_id'];
                $expires  = $session_details['expires_on'];
                $ip       = $session_details['ip'];
                $agent    = $session_details['agent'];

                # For sure this user must exists and must be valid!
                if (!$this->_is_valid_user($user_id)) { return false; }

                # Check if it is expired?
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

                # Remove old session in any case
                $this->_session_destroy($session_id);

                # Setup new user!
                Log::inf("Session was found for `{$user_id}`, user will be set!");
                $this->_user_set($user_id);
                $this->_session_set($user_id);
                return true;
            }
            else {
                Log::inf("Session found in cookies but not in database: `{$session_id}`.");
            }
        }
        else {
            Log::inf("No session found!");
            return false;
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
    protected function _session_set($user_id, $expires=null)
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
        $this->data_sessions[$q_id] = array(
            'id'         => $q_id,
            'user_id'    => $user_id,
            'expires_on' => $expires === 0 ? time() + 60 * 60 : $expires,
            'ip'         => $_SERVER['REMOTE_ADDR'],
            'agent'      => self::_clean_agent($_SERVER['HTTP_USER_AGENT']),
        );

        $this->current_session = $this->data_sessions[$q_id];
        return $this->_session_write();
    }

    /**
     * Used mostly on logout, will remove session's cookies and unset it in file.
     * --
     * @param   string  $session_id
     * --
     * @return  boolean
     */
    protected function _session_destroy($session_id)
    {
        # Remove cookies
        Cookie::remove(Cfg::get('plugs/session/cookie_name'));

        # Okay, deal with session file now...
        if (isset($this->data_sessions[$session_id])) {
            unset($this->data_sessions[$session_id]);

            $this->cleanup();

            return $this->_session_write();
        }
        else {
            Log::war("Session wasn't set, can't unset it: `{$session_id}`.");
            return true;
        }
    }

    /**
     * Will fetch all sessions (return true if successful and false if not)
     * --
     * @return  boolean
     */
    protected function _sessions_fetch()
    {
        if (file_exists($this->file_sessions)) {
            $this->data_sessions = Json::decode_file($this->file_sessions, true);
            if (is_array($this->data_sessions) && !empty($this->data_sessions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Will write all users to file, and return true if successful.
     * --
     * @return  boolean
     */
    protected function _session_write()
    {
        return Json::encode_file($this->file_sessions, $this->data_sessions);
    }

}
