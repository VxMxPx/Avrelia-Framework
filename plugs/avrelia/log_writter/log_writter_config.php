<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

$log_writter_config = array
(
    // Write specific logs to file
    'to_file'            => false,

    // When set to true, special file will be created, which will contain all logs 
    // for whole session; all messages will be included.
    // This will write logs even if `to_file` is set to false.
    'save_fatal'         => false,

    // Directory for all log files
    'directory'          => dat_path('log'),

    // The filename of regular log item
    'file_name'          => strftime('%G-%m-%d.log'),

    // Log types. Which type of messages should be saved. Options: err, war, inf
    'types'              => array('err', 'war'),

    // True: every log message will be saved individually.
    // False: all logs will be saved at the end of script execution.
    'save_individual'    => true,

    // Filename for fatal error. If `save_fatal` is set to true.
    'fatal_file'         => strftime('fatal-%G-%m-%d-%H-%M-%S.log'),
);