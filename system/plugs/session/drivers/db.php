<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\Cfg        as Cfg;
use Avrelia\Core\Str        as Str;
use Avrelia\Core\vString    as vString;
use Avrelia\Core\Log        as Log;

/**
 * Avrelia
 * ----
 * Database Session Driver
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-03-27
 */
class SessionDriverDb implements SessionDriverInterface
{
    private $CurrentUser;       # array
    private $loggedIn = false;  # boolean


    /**
     * Will construct the database object.
     * --
     * @return  void
     */
    public function __construct()
    {
        # Try to find sessions
        $this->sessionDiscover();
    }
    //-

    /**
     * Create all files / tables required by this plug to work
     * --
     * @return  boolean
     */
    public static function _create()
    {
        # Create users table (if doesn't exists)
        if (Plug::has('database')) {
            Database::execute(Cfg::get('plugs/session/db/Tables/users_table'));
            Database::execute(Cfg::get('plugs/session/db/Tables/sessions_table'));
        }
        else {
            trigger_error("Can't create, database plug must be enabled.", E_USER_ERROR);
            return false;
        }

        $Defaults = Cfg::get('plugs/session/defaults');

        foreach ($Defaults as $DefUser)
        {
            $DefUser['password'] = vString::Hash($DefUser['password'], false, true);
            Database::create($DefUser,  Cfg::get('plugs/session/users_table'));
        }

        return true;
    }
    //-

    /**
     * Destroy all elements created by this plug
     * --
     * @return  boolean
     */
    public static function _destroy()
    {
        Database::execute('DROP TABLE IF EXISTS ' . Cfg::get('plugs/session/users_table'));
        Database::execute('DROP TABLE IF EXISTS ' . Cfg::get('plugs/session/sessions_table'));
        return true;
    }
    //-


    /**
     * Will process (clean) user's agent.
     * --
     * @param   string  $agent
     * --
     * @return  string
     */
    private static function cleanAgent($agent)
    {
        return Str::clean(str_replace(' ', '_', $agent), 'aA1', '_');
    }
    //-

    /**
     * Return user's information as an array. If key provided, then only particular
     * info can be returned. For example $key = uname
     * --
     * @param   string  $key
     * --
     * @return  mixed
     */
    public function as_array($key=false)
    {
        if (!$key) {
            return $this->CurrentUser;
        }
        else {
            return isset($this->CurrentUser[$key]) ? $this->CurrentUser[$key] : false;
        }
    }
    //-

    /*  ****************************************************** *
     *          Login / Logout / isLoggedin
     *  **************************************  */

    /**
     * Login the user
     * --
     * @param   string  $username
     * @param   string  $password
     * @param   boolean $rememberMe If set to false, session will expire when user
     *                              close browser's window.
     * --
     * @return  boolean
     */
    public function login($username, $password, $rememberMe=true)
    {
        $return = $this->userSet($username, $password);

        if ($return) {
            $this->sessionSet($this->CurrentUser['id'], $rememberMe);
            return true;
        }
        else {
            return false;
        }
    }
    //-

    /**
     * Will log-in user based on id.
     * --
     * @param   integer $id
     * @param   boolean $rememberMe
     * --
     * @return  boolean
     */
    public function loginId($id, $rememberMe=true)
    {
        $return = $this->userSet($id);

        if ($return) {
            $this->sessionSet($this->CurrentUser['id'], $rememberMe);
            return true;
        }
        else {
            return false;
        }
    }
    //-

    /**
     * Will logout current user
     * ---
     * @return  void
     */
    public function logout()
    {
        if ($this->loggedIn) {
            $this->sessionDestroy($this->CurrentUser['id']);
            $this->CurrentUser    = false;
            $this->loggedIn       = false;
        }
    }
    //-

    /**
     * Is user logged in?
     * ---
     * @return  boolean
     */
    public function isLoggedin()
    {
        # All this must be true.
        return $this->CurrentUser && $this->loggedIn;
    }
    //-

    /**
     * Will reload current user's informations; Useful after an update.
     * --
     * @return  void
     */
    public function reload()
    {
        if ($this->isLoggedin()) {
            $this->userSet($this->CurrentUser['id']);
        }
    }
    //-

    /*  ****************************************************** *
     *          User Methods
     *  **************************************  */

    /**
     * Check if user can be found, and if is active. If both is true, user will
     * be set (logged in).
     * --
     * @param   mixed   $user       User's ID or username must be provided
     * @param   string  $password   If you entered password, then you must provide
     *                              username as $user, else you *must* provide id.
     * --
     * @return  boolean
     */
    private function userSet($user, $password=false)
    {
        # Select user
        if ($password) {
            $User = Database::find(Cfg::get('plugs/session/db/users_table'), array('uname' => $user));
        }
        else {
            $User = Database::find(Cfg::get('plugs/session/db/users_table'), array('id' => (int)$user));
        }

        # Valid user?
        if ($User->failed()) {
            Log::inf("Invalid username/id entered, user not found: `{$user}`.");
            return false;
        }

        $User = $User->as_array(0);

        if (!$User['active']) {
            Log::inf("User's account is not active.");
            return false;
        }

        if ($password) {
            if ($User['password'] !== vString::Hash($password, $User['password'], true)) {
                Log::inf("Invalid password entered for: `{$user}`.");
                return false;
            }
        }

        Log::inf("User logged in: `{$User['uname']}`.");
        $this->CurrentUser = $User;
        $this->loggedIn = true;

        return true;
    }
    //-

    /*  ****************************************************** *
     *          Session Methods
     *  **************************************  */

    /**
     * Will seek for user's session!
     * If one is found, the user will be auto-logged in, and true for this function
     * will be returned, else false will be returned.
     * --
     * @return  boolean
     */
    private function sessionDiscover()
    {
        # Check if we can find session id in cookies.
        if ($sessionId = Cookie::read(Cfg::get('plugs/session/cookie_name')))
        {
            # Look for session
            $SessionDetails = Database::find(
                                    Cfg::get('plugs/session/db/sessions_table'),
                                    array('id' => Str::clean($sessionId, 'aA1', '_', 400))
                                );

            # Okey we have something, check it...
            if ($SessionDetails->succeed())
            {
                $SessionDetails = $SessionDetails->as_array(0);
                $userId  = $SessionDetails['user_id'];
                $expires = $SessionDetails['expires'];
                $ip      = $SessionDetails['ip'];
                $agent   = $SessionDetails['agent'];

                # Check if it is expired?
                if ($expires < time()) {
                    Log::inf("Session was found, but it's expired.");
                    $this->sessionDestroy($sessionId);
                    return false;
                }

                # Do we have to match IP address?
                if (Cfg::get('plugs/session/require_ip')) {
                    if ($ip !== $_SERVER['REMOTE_ADDR']) {
                        Log::inf("The IP from session file: `{$ip}`, doesn't match with actual IP: `{$_SERVER['REMOTE_ADDR']}`.");
                        $this->sessionDestroy($sessionId);
                        return false;
                    }
                }

                # Do we have to match agent?
                if (Cfg::get('plugs/session/require_agent')) {
                    $currentAgent = self::cleanAgent($_SERVER['HTTP_USER_AGENT']);

                    if ($agent !== $currentAgent) {
                        Log::inf("The agent from session file: `{$agent}`, doesn't match with actual agent: `{$currentAgent}`.");
                        $this->sessionDestroy($sessionId);
                        return false;
                    }
                }

                # Try to set user now...
                if (!$this->userSet($userId)) {
                    return false;
                }

                # Remove old session in any case
                $this->sessionsClearExpired();

                return true;
            }
        }
        else {
            Log::inf("No session found!");
            return false;
        }
    }
    //-

    /**
     * Set session (set cookie, add info to sessions file)
     * --
     * @param   string  $userId
     * @param   boolean $rememberMe If set to false, session will expire when user
     *                              close browser's window.
     * @return  boolean
     */
    private function sessionSet($userId, $rememberMe=true)
    {
        # Set expires to some time in future. It 0 was set in config, then we
        # set it to expires imidietly when browser window is closed.
        if ($rememberMe === false) {
            $expires = 0;
        }
        else {
            $expires = (int) Cfg::get('plugs/session/expires');
            $expires = $expires > 0 ? $expires + time() : 0;
        }

        # Create unique id
        $qId  = time() . '_' . Str::random(20, 'aA1');

        # Store cookie
        Cookie::create(Cfg::get('plugs/session/cookie_name'), $qId, $expires);

        # Set session file
        $Session = array(
            'id'      => $qId,
            'user_id' => $userId,
            'expires' => $expires === 0 ? time() + 60 * 60 : $expires,
            'ip'      => $_SERVER['REMOTE_ADDR'],
            'agent'   => self::cleanAgent($_SERVER['HTTP_USER_AGENT']),
        );

        return Database::create($Session, Cfg::get('plugs/session/db/sessions_table'))->succeed();
    }
    //-

    /**
     * Used mostly on logout, will remove session's cookies and unset it in file.
     * --
     * @param   string  $sessionId
     * --
     * @return  boolean
     */
    private function sessionDestroy($userId)
    {
        # Remove cookies
        Cookie::remove(Cfg::get('plugs/session/cookie_name'));

        # Okay, clear session now...
        return Database::delete(Cfg::get('plugs/session/db/sessions_table'), array('user_id' => (int) $userId))->succeed();
    }
    //-

    /**
     * Will clear all expired sessions.
     * --
     * @return  void
     */
    private function sessionsClearExpired()
    {
        Database::delete(
            Cfg::get('plugs/session/db/sessions_table'),
            'WHERE expires < :expires',
            array('expires' => time())
        );
    }
    //-
}
//--
