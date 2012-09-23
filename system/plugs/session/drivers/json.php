<?php namespace Avrelia\Plug; if (!defined('AVRELIA')) die('Access is denied!');

use Avrelia\Core\FileSystem as FileSystem;
use Avrelia\Core\Json       as Json;
use Avrelia\Core\Cfg        as Cfg;
use Avrelia\Core\Str        as Str;
use Avrelia\Core\vString    as vString;
use Avrelia\Core\Log as Log;

/**
 * Avrelia
 * ----
 * Json Session Driver Class
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 * @link       http://framework.avrelia.com
 * @since      Version 0.80
 * @since      2012-01-20
 */
class SessionDriverJson implements SessionDriverInterface
{
    private $fnameUsers;        # string    Full path to the users file
    private $fnameSessions;     # string    Full path to the sessions file
    private $Users;             # array     All users
    private $Sessions;          # array     All sessions
    private $CurrentUser;       # array     Current User's Data
    private $CurrentSession;    # array     Current Session's Data
    private $loggedIn = false;  # boolean   Do we have user logged in?


    /**
     * Initialize the session - setup everything, read the cookies, etc...
     * --
     * @return  void
     */
    public function __construct()
    {
        # Set data filenames
        $this->fnameUsers    = Cfg::get('plugs/session/json/users_filename');
        $this->fnameSessions = Cfg::get('plugs/session/json/sessions_filename');

        # Load Users And Sessions
        $this->usersFetch();
        $this->sessionsFetch();

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
        FileSystem::Write(Json::encode(array()), Cfg::get('plugs/session/json/users_filename'),    false, 0777);
        FileSystem::Write(Json::encode(array()), Cfg::get('plugs/session/json/sessions_filename'), false, 0777);

        # Default users
        $Users = array();

        $Defaults = Cfg::get('plugs/session/defaults');

        foreach ($Defaults as $DefUser)
        {
            $User['id']       = self::unameToId($DefUser['uname']);
            $User['uname']    = $DefUser['uname'];
            $User['password'] = vString::Hash($DefUser['password'], false, true);
            $User['active']   = true;

            $Users[$User['id']] = $User;
        }

        return Json::encode_file(Cfg::get('plugs/session/json/users_filename'), $Users);
    }
    //-

    /**
     * Destroy all elements created by this plug
     * --
     * @return  boolean
     */
    public static function _destroy()
    {
        $r1 = FileSystem::Remove(Cfg::get('plugs/session/json/users_filename'));
        $r2 = FileSystem::Remove(Cfg::get('plugs/session/json/sessions_filename'));

        return $r1 && $r2;
    }
    //-

    /**
     * Converts username to id.
     * --
     * @param   string  $username
     * --
     * @return  string
     */
    private static function unameToId($username)
    {
        return Str::clean(
                    Str::symbols_to_words($username), 
                    'aA1'
                );
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
        return Str::clean(
                    str_replace(' ', '_', $agent), 
                    'aA1', 
                    '_'
                );
    }
    //-

    /*  ****************************************************** *
     *          Login / Logout / isLoggedin
     *  **************************************  */

    /**
     * Will login the user (create new session from input)
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
        $id = self::unameToId($username);

        # Do we have valid user?
        if (!$User = $this->userValid($id)) {
            return false;
        }

        # Password match?
        if ($User['password'] !== vString::Hash($password, $User['password'], true)) {
            Log::inf("Invalid password for: `{$username}`.");
            return false;
        }

        # Okay, set session and current user
        $this->userSet($User['id']);
        $this->sessionSet($User['id'], $rememberMe);

        return true;
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
        # Do we have valid user?
        if (!$User = $this->userValid($id)) {
            return false;
        }

        # Okay, set session and current user
        $this->userSet($User['id']);
        $this->sessionSet($User['id'], $rememberMe);

        return true;
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
            $this->sessionDestroy($this->CurrentSession['id']);
            $this->CurrentSession = false;
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
        return $this->CurrentSession && $this->CurrentUser && $this->loggedIn;
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
            $this->usersFetch();
            $this->userSet($this->CurrentUser['id']);
        }
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
     *          User Methods
     *  **************************************  */

    /**
     * Set user (set all user's data)
     * --
     * @param   string  $userId
     * --
     * @return  boolean
     */
    private function userSet($userId)
    {
        if (!isset($this->Users[$userId])) {
            Log::war("Can't set user, not found: `{$userId}`.");
            return false;
        }

        $this->CurrentUser = $this->Users[$userId];
        $this->loggedIn    = true;
        return true;
    }
    //-

    /**
     * Check if user can be found, and if is active. If both is true,
     * the User array will be returned, else false.
     * --
     * @param   string  $userId
     * --
     * @return  mixed
     */
    private function userValid($userId)
    {
        # Can we find this user?
        if (!isset($this->Users[$userId])) {
            Log::inf("User with this ID was not found: `{$id}`");
            return false;
        }

        # Is active?
        if (!isset($this->Users[$userId]['active']) || $this->Users[$userId]['active'] !== true) {
            Log::inf("User's account isn't active: `{$userId}`.");
            return false;
        }

        return $this->Users[$userId];
    }
    //-

    /**
     * Will fetch all users (return true if successful and false if not)
     * --
     * @return  boolean
     */
    private function usersFetch()
    {
        if (file_exists($this->fnameUsers)) {
            $this->Users = Json::decode_file($this->fnameUsers, true);
            if (is_array($this->Users) && !empty($this->Users)) {
                return true;
            }
        }

        return false;
    }
    //-

    /**
     * Will write all users to file, and return true if successful.
     * --
     * @return  boolean
     */
    private function usersWrite()
    {
        return Json::encode_file($this->fnameUsers, $this->Users);
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
            # Okey we have something, check it...
            if (isset($this->Sessions[$sessionId]))
            {
                $SessionDetails = $this->Sessions[$sessionId];
                $userId  = $SessionDetails['user_id'];
                $expires = $SessionDetails['expires'];
                $ip      = $SessionDetails['ip'];
                $agent   = $SessionDetails['agent'];

                # For sure this user must exists and must be valid!
                if (!$this->userValid($userId)) {
                    return false;
                }

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

                # Remove old session in any case
                $this->sessionDestroy($sessionId);

                # Setup new user!
                Log::inf("Session was found for `{$userId}`, user will be set!");
                $this->userSet($userId);
                $this->sessionSet($userId);
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
     * --
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
        $this->Sessions[$qId] = array(
            'id'      => $qId,
            'user_id' => $userId,
            'expires' => $expires === 0 ? time() + 60 * 60 : $expires,
            'ip'      => $_SERVER['REMOTE_ADDR'],
            'agent'   => self::cleanAgent($_SERVER['HTTP_USER_AGENT']),
        );

        $this->CurrentSession = $this->Sessions[$qId];
        return $this->sessionsWrite();
    }
    //-

    /**
     * Used mostly on logout, will remove session's cookies and unset it in file.
     * --
     * @param   string  $sessionId
     * --
     * @return  boolean
     */
    private function sessionDestroy($sessionId)
    {
        # Remove cookies
        Cookie::remove(Cfg::get('plugs/session/cookie_name'));

        # Okay, deal with session file now...
        if (isset($this->Sessions[$sessionId])) {
            unset($this->Sessions[$sessionId]);

            # Write changes to file
            return $this->sessionsWrite();
        }
        else {
            Log::war("Session wasn't set anyway, can't unset it: `{$sessionId}`.");
            return true;
        }
    }
    //-

    /**
     * Will clear all expired sessions, and return the amount of removed items.
     * --
     * @return  integer
     */
    private function sessionsClearExpired()
    {
        $removed = 0;

        foreach ($this->Sessions as $id => $Session)
        {
            if ($Session['expires'] < time()) {
                unset($this->Sessions[$id]);
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->sessionsWrite();
        }

        return $removed;
    }
    //-

    /**
     * Will fetch all sessions (return true if successful and false if not)
     * --
     * @return  boolean
     */
    private function sessionsFetch()
    {
        if (file_exists($this->fnameSessions)) {
            $this->Sessions = Json::decode_file($this->fnameSessions, true);
            if (is_array($this->Sessions) && !empty($this->Sessions)) {
                return true;
            }
        }

        return false;
    }
    //-

    /**
     * Will write all users to file, and return true if successful.
     * --
     * @return  boolean
     */
    private function sessionsWrite()
    {
        return Json::encode_file($this->fnameSessions, $this->Sessions);
    }
    //-

}
//--
