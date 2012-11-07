<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

$session_config = array
(
    # Session expiration: default = One week;
    # If you want the session to expire on browser's window close,
    # then set this value to 0.
    'expires'          => 60*60*24*7,

    # The name of the cookie set for session
    'cookie_name'      => 'avrelia_session',

    # For valid session require the user's IP to match
    'require_ip'       => false,

    # For valid session require the user's agent to match
    'require_agent'    => true,

    # Require field called is_active, and this field must be set to "1"
    'require_active'   => false,

    # If set to true, databases, files needed for this plug to operate will be
    # auto created when plug is enabled.
    'create_on_enable' => true,

    # If true, it will delete, remove all databases and files when this plug
    # is disabled. Good for testing perhaps, not so got when in production.
    'drop_on_disable'  => false,

    # Driver:
    #   JSON: for flat file storage
    #   DB  : use database
    'driver'          => 'json',

    # JSON driver configuration
    'json'            => array
    (
        'users_filename'    => Plug::get_database_path('session/users.json'),
        'sessions_filename' => Plug::get_database_path('session/sessions.json'),
    ),

    # Database driver configuration
    'db'        => array
    (
        # Table must have at least following fields: id, [is_active]
        'users_table'       => 'users',

        # Table must have at least following fields: id, user_id, ip, agent, expires_on
        'sessions_table'    => 'users_sessions',

        # Tables to be auto-created if not exists, set to false, if you don't
        # want to create it.
        'tables'            => array(
            'users_table'   =>
                'CREATE TABLE IF NOT EXISTS users (
                    id          INTEGER PRIMARY KEY AUTOINCREMENT   NOT NULL,
                    uname       VARCHAR(200)                        NOT NULL,
                    password    TEXT                                NOT NULL,
                    is_active   INTEGER(1)                          NOT NULL
                )',
            'sessions_table' =>
                'CREATE TABLE IF NOT EXISTS users_sessions (
                    id          VARCHAR(255)    NOT NULL,
                    user_id     VARCHAR(255)    NOT NULL,
                    ip          VARCHAR(16)     NOT NULL,
                    agent       VARCHAR(255)    NOT NULL,
                    expires_on  INTEGER(12)     NOT NULL
                )',
        ),
    ),

    # Default users to insert upon initialization of this plug, it will be created
    # in both cases either JSON format or db format.
    'defaults'   => array
    (
        array(
            # root@domain.tld / root
            'id'        => null,
            'uname'     => 'root@domain.tld',
            'password'  => 'root',
            'active'    => true,
        ),
    ),

);
