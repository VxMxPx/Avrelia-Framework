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
 * We'll load all base classes now
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
 * Initialize base system classes now.
 */
if (!_inc_core_app_class_('loader'))
    { class Loader extends Loader_Base {} }
_call_include_method_('Loader');

if (!_inc_core_app_class_('v_array'))
    { class vArray extends vArray_Base {} }
_call_include_method_('vArray');

if (!_inc_core_app_class_('v_boolean'))
    { class vBoolean extends vBoolean_Base {} }
_call_include_method_('vBoolean');

if (!_inc_core_app_class_('v_string'))
    { class vString extends vString_Base {} }
_call_include_method_('vString');

if (!_inc_core_app_class_('benchmark'))
    { class Benchmark extends Benchmark_Base {} }
_call_include_method_('Benchmark');

if (!_inc_core_app_class_('cfg'))
    { class Cfg extends Cfg_Base {} }
_call_include_method_('Cfg');

if (!_inc_core_app_class_('log'))
    { class Log extends Log_Base {} }
_call_include_method_('Log');

if (!_inc_core_app_class_('dot'))
    { class Dot extends Dot_Base {} }
_call_include_method_('Dot');

if (!_inc_core_app_class_('dispatcher'))
    { class Dispatcher extends Dispatcher_Base {} }
_call_include_method_('Dispatcher');

if (!_inc_core_app_class_('event'))
    { class Event extends Event_Base {} }
_call_include_method_('Event');

if (!_inc_core_app_class_('file_system'))
    { class FileSystem extends FileSystem_Base {} }
_call_include_method_('FileSystem');

if (!_inc_core_app_class_('http'))
    { class HTTP extends HTTP_Base {} }
_call_include_method_('HTTP');

if (!_inc_core_app_class_('input'))
    { class Input extends Input_Base {} }
_call_include_method_('Input');

if (!_inc_core_app_class_('language'))
    { class Language extends Language_Base {} }
_call_include_method_('Language');

if (!_inc_core_app_class_('model'))
    { class Model extends Model_Base {} }
_call_include_method_('Model');

if (!_inc_core_app_class_('output'))
    { class Output extends Output_Base {} }
_call_include_method_('Output');

if (!_inc_core_app_class_('plug'))
    { class Plug extends Plug_Base {} }
_call_include_method_('Plug');

if (!_inc_core_app_class_('util'))
    { class Util extends Util_Base {} }
_call_include_method_('Util');

if (!_inc_core_app_class_('view'))
    { class View extends View_Base {} }
_call_include_method_('View');

if (!class_exists('ViewAssign', false))
    { class ViewAssign extends ViewAssign_Base {} }