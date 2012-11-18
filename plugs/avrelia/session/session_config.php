<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

$session_config = array
(
    # Session expiration: default = One week;
    # If you want the session to expire on browser's window close,
    # then set this value to 0.
    'expires'            => 60*60*24*7,

    # The name of the cookie set for session
    'cookie_name'        => 'avrelia_session',

    # For valid session require the user's IP to match
    'require_ip'         => false,

    # For valid session require the user's agent to match
    'require_agent'      => true,

    # Require field called is_active, and this field must be set to "1"
    'require_active'     => false,

    # If set to true, database tables needed for this plug to operate will be
    # auto created when the plug is enabled.
    'create_on_enable'   => true,

    # If true, it will delete, remove database tables when this plug
    # is disabled. Good for testing perhaps, not so got when in production.
    'drop_on_disable'    => false,

    # Table must have at least following fields: 
    # id            -- INT User's ID 
    # [is_active]   -- INT Is user's account active, only if you set 
    #                      "require_acive" to be true
    'users_table'        => 'users',

    # Table must have at least following fields: 
    # id               -- VARCHAR(255) Session's ID
    # user_id          -- INT          User's ID
    # ip               -- VARCHAR(16)  User's IP Address
    # agent            -- VARCHAR(255) User's agent
    # created_on       -- INTEGER(14)  Creation time-stamp value
    # expires_on       -- INTEGER(12)  Expiration time-stamp
    # expiration_value -- INTEGER(10)  The actual expiration value, can be
    #                                  Value in seconds, zero means expires on
    #                                  browser window close.
    'sessions_table'     => 'users_sessions',

    # Tables to be auto-created if not exists.
    # Set to false, if you don't want to create and of those tables.
    # It's suggested that you use variable {{table_name}}, so that the name of
    # table will be auto-guessed, based what you set for the users_table or 
    # sessions_table key.
    'tables'             => array
    (
        'users_table'    =>
            'CREATE TABLE IF NOT EXISTS {{table_name}} (
                id          INTEGER PRIMARY KEY AUTOINCREMENT   NOT NULL,
                uname       VARCHAR(200)                        NOT NULL,
                password    VARCHAR(80)                         NOT NULL,
                is_active   INTEGER(1)                          NOT NULL
            )',
        'sessions_table' =>
            'CREATE TABLE IF NOT EXISTS {{table_name}} (
                id                VARCHAR(255)    NOT NULL,
                user_id           VARCHAR(255)    NOT NULL,
                ip                VARCHAR(16)     NOT NULL,
                agent             VARCHAR(255)    NOT NULL,
                expires_on        INTEGER(12)     NOT NULL,
                expiration_value  INTEGER(10)     NOT NULL
            )'
    ),
);