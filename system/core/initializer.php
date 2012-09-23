<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Initializer
 * -----------------------------------------------------------------------------
 * Initialize all core (base) classes, check if class exists in application's
 * folder, and load that one, or default.
 * ----
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */

/**
 * Include core application's class
 * @param  string $file_name
 * @return boolean
 */
function _inc_core_app_class_($file_name)
{
    $file_name = app_path("core/{$file_name}.php");
    if (file_exists($file_name)) {
        include $file_name;
        return true;
    }

    return false;
}

/**
 * Call static method _on_include_ if exists.
 * @param  string $class_name
 * @return void
 */
function _call_include_method_($class_name)
{
    if (method_exists($class_name, '_on_include_')) { 
        call_user_func(array($class_name, '_on_include_')); }
}

/* -----------------------------------------------------------------------------
 * We'll load all base (files) classes now, and make them available
 */
$base_classes = scandir(sys_path('core/base'));

if (empty($base_classes)) { 
    trigger_error("Couldn't find any class in: {$base_classes}", E_USER_ERROR); 
}

foreach ($base_classes as $base_file) {
    if (substr($base_file, -4, 4) !== '.php') { continue; }
    include sys_path("core/base/{$base_file}");
}

/* -----------------------------------------------------------------------------
 * Classes map
 */
$classes_map = array(
    'Avrelia\\Core\\Loader'     => 'Loader',
    'Avrelia\\Core\\Arr'        => 'Arr',
    'Avrelia\\Core\\Str'        => 'Str',
    'Avrelia\\Core\\Bool'       => 'Bool',
    'Avrelia\\Core\\Json'       => 'Json',
    'Avrelia\\Core\\vString'    => 'vString',
    'Avrelia\\Core\\Benchmark'  => 'Benchmark',
    'Avrelia\\Core\\Cfg'        => 'Cfg',
    'Avrelia\\Core\\Log'        => 'Log',
    'Avrelia\\Core\\Dot'        => 'Dot',
    'Avrelia\\Core\\Dispatcher' => 'Dispatcher',
    'Avrelia\\Core\\Event'      => 'Event',
    'Avrelia\\Core\\FileSystem' => 'FileSystem',
    'Avrelia\\Core\\Http'       => 'Http',
    'Avrelia\\Core\\Input'      => 'Input',
    'Avrelia\\Core\\Language'   => 'Language',
    'Avrelia\\Core\\Model'      => 'Model',
    'Avrelia\\Core\\Output'     => 'Output',
    'Avrelia\\Core\\Plug'       => 'Plug',
    'Avrelia\\Core\\Util'       => 'Util',
    'Avrelia\\Core\\View'       => 'View',
    'Avrelia\\Core\\ViewAssign' => 'ViewAssign',
);

// Will set aliases for all core classes and run on include function...
foreach ($classes_map as $class => $alias)
{
    class_alias($class, $alias);
    _call_include_method_($alias);
}