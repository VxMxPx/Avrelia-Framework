<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

# ============================================================ #
#                WARNING: DON'T EDIT THIS FILE!                #
# ------------------------------------------------------------ #
#  If you want to change anything, put the file with same name #
#      into application/config folder, to rewrite values.      #
# ============================================================ #


# Avrelia Framework Main Configuration
$avrelia_config = array
(
    'core' => array
    (
        # When set to true application will appear offline, and costume message
        # or view will be displayed. See the offline_message setting.
        'offline'         => false,

        # Default Timezone. To see the list of supported timezones visit:
        # http://php.net/manual/en/timezones.php
        'timezone'        => 'UTC',

        'language'        => array(
            # List of default languages, this is used when loading language 
            # with % in filename.
            # Examples: my_language.%.lng, 'defaults' = ['ru', 'en', 'de']
            #       --> my_language.ru.lng ? NO
            #       --> my_language.en.lng ? YES --> Load my_language.en.lng
            #       --> my_language.de.lng
            'defaults'    => array('en'),

            # Convert manually written \n character in language files to <br />.
            'n_to_br'     => true,
        ),

        'input'           => array(
            # Should regular _GET segments be ignored, when matching route.
            # For example: ?year=2012&month=04 will be ignored.
            # NOTE: This only ignore get in Route::on() method, you can still
            #       retrieve get segments through Input::get('year') OR
            #       $_GET['year']
            'ignore_get_segmments'  => true,

            # Means that year=2012/month=04 is the same as ?year=2012&month=04
            # The above example can be retrieved rethought Input::get('year')
            # OR $_GET['year']. When this set to true, segments with equal
            # character will be ignored by Route::on()
            'eq_segments_as_get'    => true,

            # When set to array [key, value], the GET request will be threated
            # as being DELETE. For example: 
            # Using: ['_method', 'delete']
            # ----> users/1?_method=delete become DELETE request.
            # 
            # Set to false to turn off this functionality.
            'delete_from_get'       => array('_method', 'delete'),

            # When set to array [key, value], the POST request will be threated
            # as being DELETE. For example:
            # Using: ['_method', 'delete']
            # ----> <input name="_method" value="delete"> become DELETE request.
            # 
            # Set to false to turn off this functionality.
            'delete_from_post'      => array('_method', 'delete'),

            # When set to array [key, value], the POST request will be threated
            # as being PUT. For example:
            # Using: ['_method', 'put']
            # ----> <input name="_method" value="post"> become PUT request.
            # 
            # Set to false to turn off this functionality.
            'put_from_post'         => array('_method', 'put'),
        ),

        'dispatcher'      => array(
            # If set to true, dispatcher will check controller's response.
            # If response will be === false, the 404 will be displayed
            # (as if no route was not found!)
            'check_response'        => false,
        ),

        'http'            => array(
            # If set to false, all calls to Http::redirect will be ignored. 
            # In production this should be aylways set to true.
            'allow_redirects'       => true,
        ),

        'dir'             => array(
            # Folders which should be ignored when copying / removing / moving 
            # multiple items.
            'ignore'                => array('.svn'),

            # Default permission mode, when creating new directories.
            # Must be octal number, with leading zero.
            'default_mode'          => 0755,            
        ),

        'file'            => array(
            # Files which should be ignored when copying / removing / moving 
            # multiple items.
            'ignore'                => array('Thumbs.db'),
        ),

        'tests'           => array(
            # Command to be executed when `edit` is run in test.
            # This supposed to open file in particular editor.
            'editor_command'        => 'sublime-text %s &',
        ),

        'plug'            => array
        (
            # List of enabled plugs. All plugs must be added to this list before
            # they can be used.
            'enabled'     => array(),

            # List of plugs which will be always auto-loaded at the beginning.
            # ----
            # NOTE: Plug must be on the "enabled list" in order to be auto loaded.
            'auto_load'   => array(),

            # Plugs' public directory.
            # ----
            # WARNING: This MUST be only directory name and not full path, 
            #          as this is used to create folder in database, etc...
            'public_dir'  => 'plugs',

            # When debug is true, plug's public folder will be erased and copied 
            # again for every page request. This is useful for plug development. 
            # ----
            # WARNING: This should never be true in production!
            'debug'       => false,
        ),

        'log' => array
        (
            # PHP system errors are handled by Avrelia Framework, therefore 
            # then need to be simplified to fit log methods.
            # Framework has only three levels: war, inf, err and react 
            # differently when one of them is triggered.
            # ----
            # WARNING: ERR type will stop script execution.
            'map'     => array
            (
                E_ERROR              => 'err',
                E_WARNING            => 'war',
                E_PARSE              => 'err',
                E_NOTICE             => 'inf',
                E_CORE_ERROR         => 'err',
                E_CORE_WARNING       => 'war',
                E_COMPILE_ERROR      => 'err',
                E_COMPILE_WARNING    => 'war',
                E_USER_ERROR         => 'err',
                E_USER_WARNING       => 'war',
                E_USER_NOTICE        => 'inf',
                E_STRICT             => 'war',
                E_RECOVERABLE_ERROR  => 'err',
                E_DEPRECATED         => 'war',
                E_USER_DEPRECATED    => 'war',
            ),

            # Is log enabled (writing to file)?
            # Please before you enabled log, make sure your log folder is writable.
            'enabled' => false,

            # Full log path where all log messages will be saved.
            # You must provide full absolute path.
            'path'    => APPPATH . '/log/' . date('Y') . '/' . date('m') . '/' . 
                         date('d') . '.log',

            # Log types. Select which type of messages should be saved. 
            # The "inf" type isn't recommended to be saved.
            'types'   => array
            (
                'err' => true,
                'war' => true,
                'inf' => false
            ),

            # PHP system errors are handled by Avrelia Framework, 
            # therefore we're simplifying them. 
            # Framework has only three levels: WAR, INF, ERR, (OK) 
            # and react differently when one of them is triggered.
            # Note: ERR type will stop script execution.
            'map'     => array
            (
                E_ERROR              => 'err',
                E_WARNING            => 'war',
                E_PARSE              => 'err',
                E_NOTICE             => 'inf',
                E_CORE_ERROR         => 'err',
                E_CORE_WARNING       => 'war',
                E_COMPILE_ERROR      => 'err',
                E_COMPILE_WARNING    => 'war',
                E_USER_ERROR         => 'err',
                E_USER_WARNING       => 'war',
                E_USER_NOTICE        => 'inf',
                E_STRICT             => 'war',
                E_RECOVERABLE_ERROR  => 'err',
                E_DEPRECATED         => 'war',
                E_USER_DEPRECATED    => 'war',
            ),

            # Set to true: every log message will be saved individually.
            # Set to false: all logs will be saved at the end of script execution.
            'write_individual'   => true,

            # Write all on fatal error. Set to true, special file will be created,
            # which will contain only one fatal event, 
            # with whole session informations, it will include ERR, WAR, INF.
            'write_all_on_fatal' => true,

            # Filename for fatal error.
            # Will be used only if you set 'write_all_on_fatal' to true.
            'fatal_path'         => APPPATH . '/log/' . date('Y') . '/' . date('m') 
                                    . '/fatal/' . date('Y-m-d__H-i-s') . '.log',

        ),
    ),
);
