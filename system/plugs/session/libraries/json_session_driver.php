<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

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

class jsonSessionDriver
{
	# Kee all info about current session
	private $CurrentSession = false;

	# Users database
	private $AllUsers       = array();

	# Sessions file
	private $Sessions       = array();

	# Component's config
	private $Config         = array();

	/**
	 * Will start the session, load all users.
	 */
	public function __construct($Config)
	{
		$this->Config = $Config;

		# Load all users
		$this->usersFileReload();

		# Load sessions
		$this->sessionFileReload();

		# Finally, try to read session, if exists...
		$this->sessionFind();
	}
	//-

	/**
	 * Do we have all files we need?
	 * ---
	 * @param array $Config
	 * ---
	 * @return bool
	 */
	public static function _canConstruct($Config)
	{
		return file_exists($Config['json']['users_filename']) && file_exists($Config['json']['sessions_filename']);
	}
	//-

	/**
	 * Will create files required for session to work...
	 * ---
	 * @param array $Config
	 * ---
	 * @return bool
	 */
	public static function _doEnable($Config)
	{
		$isOne = FileSystem::Write(uJSON::Encode($Config['users']), $Config['json']['users_filename'],    false, 0777);
		$isTwo = FileSystem::Write('{}',                            $Config['json']['sessions_filename'], false, 0777);

		return $isOne && $isTwo;
	}
	//-

	/*  ****************************************************** *
	 *          Login / Logout
	 *  **************************************  */

	/**
	 * Will login the user
	 * ---
	 * @param string $email
	 * @param string $password
	 * ---
	 * @return bool
	 */
	public function doLogin($email, $password)
	{
		$user = $this->userGetByEmail($email);

		if ($user) {
			if ($user['password'] == vString::Hash($password, $user['salt'])) {
				# We have valid user, set it up!
				$this->userSetup($user);
				return true;
			}
			else {
				Log::Add('WAR', "Invalid password for: `{$email}`.", __LINE__, __FILE__);
			}
		}
		else {
			Log::Add('WAR', "Invalid username or password for: `{$email}`.", __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Will logout current user
	 * ---
	 * @return null
	 */
	public function doLogout()
	{
		if ($this->isLoggedin()) {
			$this->sessionRemove($this->CurrentSession['Session']['id']);
			$this->CurrentSession = array();
		}
	}
	//-

	/*  ****************************************************** *
	 *          User Related Methods
	 *  **************************************  */

	/**
	 * Will get user by e-mail address, or return false, if user can't be found.
	 * ---
	 * @param string $email
	 * ---
	 * @return array / bool
	 */
	public function userGetByEmail($email)
	{
		# Generate ID
		$id = vString::Hash($email);

		return $this->userGetById($id);
	}
	//-

	/**
	 * Will get user by e-mail address, or return false, if user can't be found.
	 * ---
	 * @param string $id
	 * ---
	 * @return array / bool
	 */
	public function userGetById($id)
	{
		# Refresh Users
		$this->usersFileReload();

		# Check if we have user
		if (isset($this->AllUsers[$id])) {
			return $this->AllUsers[$id];
		}
		else {
			return false;
		}
	}
	//-

	/**
	 * Get prticular user's info
	 * ---
	 * @param string $what
	 * ---
	 * @return string
	 */
	public function userGetInfo($what)
	{
		if (!$this->isLoggedin()) { return false; }

		return vArray::GetByPath($what, $this->CurrentSession, false);
	}
	//-

	/**
	 * Will setup current user / session!
	 * ---
	 * @param array $User
	 * ---
	 * @return null
	 */
	private function userSetup($User)
	{
		$this->CurrentSession = $User;
		$this->CurrentSession['loggedin'] = true;
		$this->sessionSet($User['id']);
	}
	//-

	/**
	 * Is user logged in?
	 * ---
	 * @return bool
	 */
	public function isLoggedin()
	{
		if ($this->CurrentSession && !empty($this->CurrentSession)) {
			return isset($this->CurrentSession['loggedin']) && $this->CurrentSession['loggedin'];
		}
		return false;
	}
	//-

	/*  ****************************************************** *
	 *          Multiple Users Related Method
	 *  **************************************  */

	/**
	 * Will get all users
	 * ---
	 * @return array
	 */
	public function usersGetAll()
	{
		return $this->AllUsers;
	}
	//-

	/**
	 * Edit or create an user. If you provide id, the user will bed edited, else
	 * created.
	 * ---
	 * @param array $Data
	 * @param string $id
	 * ---
	 * @return bool
	 */
	public function usersEdit($Data, $id=false)
	{
		# Reload data
		$this->usersFileReload();

		# Set empty user
		$User = array();

		# If we're creating new user, we need to have email set
		if (!$id && !isset($Data['email'])) {
			Log::Add('WAR', "Email must be set, if you're creating new user.", __LINE__, __FILE__);
			return false;
		}

		# if we're updating, we need to have current user in database
		if ($id) {
			$User = $this->userGetById($id);


			$User['hashed_password'] = $User['password'];
			unset($User['password']);

			if (!$User) {
				Log::Add('WAR', "Invalid user's id provided: `{$id}`.", __LINE__, __FILE__);
				return false;
			}
		}

		# Set values
		$User = array_merge($User, $Data);

		# Genera new id & set it
		$newId = vString::Hash($User['email']);
		$User['id'] = $newId;

		# If we have password, then re-generate it!
		if (isset($Data['password'])) {
			if (!$id) {
				$User['salt']     = vString::Random(rand(5,8), 'a A 1 s');
				$User['password'] = vString::Hash($Data['password'], $User['salt']);
			}
			else {
				$User['salt']     = vString::Random(rand(5,8), 'a A 1 s');
				$User['password'] = vString::Hash($Data['password'], $User['salt']);
			}
		}
		else {
			$User['password'] = $User['hashed_password'];
		}

		unset($User['hashed_password']);

		# User previous user
		if ($id && isset($this->AllUsers[$id])) {
			unset($this->AllUsers[$id]);
		}

		# Set new user
		$this->AllUsers[$newId] = $User;

		$return = $this->usersFileSave();

		# If we're updating current user, then we'll log him in! :)
		if ($id == $this->CurrentSession['id']) {
			$this->doLogout();
			$this->userSetup($User);
		}

		return $return;
	}
	//-

	/**
	 * Delete particular user, by id
	 * ---
	 * @param string $id
	 * ---
	 * @return bool
	 */
	public function usersDelete($id)
	{
		$this->usersFileReload();

		if (isset($this->AllUsers[$id])) {
			unset($this->AllUsers[$id]);
		}

		return $this->usersFileSave();
	}
	//-

	/**
	 * Will reload users file
	 * ---
	 * @return bool
	 */
	private function usersFileReload()
	{
		# Load all users
		if (file_exists($this->Config['json']['users_filename'])) {
			$this->AllUsers = uJSON::DecodeFile($this->Config['json']['users_filename'], true);
			return true;
		}
		else {
			Log::Add('ERR', "Can't load users database!", __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Will store users file
	 * ---
	 * @return bool
	 */
	private function usersFileSave()
	{
		return uJSON::EncodeFile($this->Config['json']['users_filename'], $this->AllUsers);
	}
	//-

	/*  ****************************************************** *
	 *          Session Related Methods
	 *  **************************************  */

	/**
	 * Will seek user's session!
	 * If one is found (and is ok), the user will be initialized and 'true'
	 * will be returned. Else, false will be returned.
	 * ---
	 * @return bool
	 */
	private function sessionFind()
	{
		# Check if we can find session id in cookies.
		if ($sessionId = uCookie::Read('csession')) {
			# Okey we have something, clean it, and check it...
			$sessionId = explode('_', $sessionId, 2);
			$sessionId = $sessionId[0] . '_' . vString::Hash($sessionId[1]);

			# Do we have it?
			if (isset($this->Sessions[$sessionId])) {
				$SessionDetails = $this->Sessions[$sessionId];
				$userId  = $SessionDetails['user_id'];
				$expires = $SessionDetails['expires'];
				$ip      = $SessionDetails['ip'];

				# Stil valid?
				if ($ip == $_SERVER['REMOTE_ADDR'] && $expires > time()) {

					# Reload, remove old session in any case
					$this->sessionRemove($sessionId);

					# Setup new user!
					if ($User = $this->userGetById($userId)) {
						Log::Add('INF', "Session was found for `{$User['email']}`, user will be set!", __LINE__, __FILE__);
						$this->userSetup($User);
						return true;
					}
					else {
						Log::Add('ERR', "We found user `{$userId}` in session, but not in users collection.", __LINE__, __FILE__);
						return false;
					}
				}
				else {
					Log::Add('INF', "We found session, but it seems to be expired or IP doesn't match!", __LINE__, __FILE__);
					$this->sessionRemove($sessionId);
					return false;
				}
			}
		}
		else {
			Log::Add('INF', "No session found!", __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Will crear all expired sessions, and return amount of removed sessions.
	 * ---
	 * @return int
	 */
	private function clearExpiredSessions()
	{
		$this->sessionFileReload();
		$removed = 0;

		foreach ($this->Sessions as $id => $Session)
		{
			if ($Session['expires'] > time()) {
				unset($this->Sessions[$id]);
				$removed++;
			}
		}

		if ($removed > 0) {
			$this->sessionFileSave();
		}

		return $removed;
	}
	//-

	/**
	 * Will remove session from file and cookies...
	 * ---
	 * @param string $sessionId
	 * ---
	 * @return void
	 */
	private function sessionRemove($sessionId)
	{
		# Remove cookies
		uCookie::Remove('csession');

		# Okay, deal with session file now...
		$this->clearExpiredSessions();
		unset($this->Sessions[$sessionId]);

		return $this->sessionFileSave();
	}
	//-

	/**
	 * Will reload session file
	 * ---
	 * @return bool
	 */
	private function sessionFileReload()
	{
		# Load all sessions
		if (file_exists($this->Config['json']['sessions_filename'])) {
			$this->Sessions = uJSON::DecodeFile($this->Config['json']['sessions_filename'], true);
			return true;
		}
		else {
			Log::Add('ERR', "Can't load sessions database!", __LINE__, __FILE__);
			return false;
		}
	}
	//-

	/**
	 * Will store sessions file
	 * ---
	 * @return bool
	 */
	private function sessionFileSave()
	{
		return uJSON::EncodeFile($this->Config['json']['sessions_filename'], $this->Sessions);
	}
	//-

	/**
	 * Set new session for user
	 * ---
	 * @param string $userId
	 * ---
	 * @return void
	 */
	private function sessionSet($userId)
	{
		# Make new Q session id
		$time            = time();
		$randomString    = vString::Random(25, 'a A 1');
		$sessionIdCookie = $time . '_' . $randomString;
		$sessionIdFile   = $time . '_' . vString::Hash($randomString);

		# Expires
		$expires = time() + (int) $this->Config['expires'];

		# Store cookie
		uCookie::Create('csession', $sessionIdCookie, $expires);

		# Set session file
		$this->sessionFileReload();
		$this->Sessions[$sessionIdFile] = array(
			'id'      => $sessionIdFile,
			'user_id' => $userId,
			'expires' => $expires,
			'ip'      => $_SERVER['REMOTE_ADDR'],
		);

		# Add it to current user, then save it
		$this->CurrentSession['Session'] = $this->Sessions[$sessionIdFile];

		return $this->sessionFileSave();
	}
	//-

}
//--
