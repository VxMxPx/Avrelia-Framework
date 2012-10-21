<?php namespace Plug\Avrelia; if (!defined('AVRELIA')) die('Access is denied!');

/**
 * Session Driver Interface
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
interface SessionDriverInterface
{
    /**
     * Create all files / tables required by this plug to work
     * --
     * @return  boolean
     */
    static function _on_enable_();

    /**
     * Destroy all elements created by this plug
     * --
     * @return  boolean
     */
    static function _on_disable_();


    /**
     * Create new session by id
     * --
     * @param   integer $id      User's id as in DB or JSON
     * @param   integer $expires Null for default or costume expiration in seconds,
     *                           0, to expires when browser is closed.
     * --
     * @return  boolean
     */
    function create($id, $expires=null);

    /**
     * Destroy current session
     * --
     * @return void
     */
    function destroy();

    /**
     * Is session set?
     * --
     * @return  boolean
     */
    function has();

    /**
     * Will reload current session.
     * Useful after updating user's informations.
     * --
     * @return  void
     */
    function reload();

    /**
     * Will clear all expired sessions.
     * --
     * @return  void
     */
    public function cleanup();

    /**
     * Get particular information about user (session).
     * --
     * @param  string  $key
     * @param  mixed   $default
     * --
     * @return mixed
     */
    function get($key, $default=false);

    /**
     * Return user's information as an array.
     * --
     * @return array
     */
    function as_array();

    /**
     * List all sessions.
     * --
     * @return array
     */
    function list_all();

    /**
     * Clear all sessions.
     * --
     * @return void
     */
    function clear_all();
}
