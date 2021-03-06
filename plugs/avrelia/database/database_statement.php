<?php

namespace Plug\Avrelia;

use Avrelia\Core\Event as Event;

/**
 * DatabaseStatement
 * -----------------------------------------------------------------------------
 * @author     Avrelia.com (Marko Gajst)
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://framework.avrelia.com/license
 */
class DatabaseStatement
{
    /**
     * @var string  $statement  An SQL statement which will be executed.
     */
    protected $statement;

    /**
     * @var array   An array of all values which need to be binded.
     */
    protected $bind;


    /**
     * Construct object with some initial statement.
     * --
     * @param   string  $statement
     * --
     * @return  void
     */
    public function __construct($statement)
        { $this->statement = $statement; }

    /**
     * Add string to the end of the statement.
     * --
     * @param   string  $statement
     * --
     * @return  $this
     */
    public function add($statement)
    {
        $this->statement .= ' ' . $statement;
        return $this;
    }

    /**
     * Replace whole statement with something else.
     * --
     * @param   string  $statement
     * --
     * @return  $this
     */
    public function replace($statement)
    {
        $this->statement = $statement;
        return $this;
    }

    /**
     * Will bind values. Can accept $key as array or as string,
     * If you entered $key as string, then you must enter $val also.
     * --
     * @param   mixed   $key
     * @param   string  $val
     * --
     * @return  $this
     */
    public function bind($key, $val=false)
    {
        if ($key) {
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    $this->bind[$k] = $v;
                }
            }
            else {
                $this->bind[$key] = $val;
            }
        }

        return $this;
    }

    /**
     * Execute statement and return DatabaseResult object.
     * --
     * @return  DatabaseResult
     */
    public function execute()
    {
        $params = array(
            'statement' => $this->statement,
            'bind'      => $this->bind
        );

        Event::trigger('/plugs/avrelia/database/statement/execute', $params);
        return Database::get_driver()->prepare($params['statement'], $params['bind']);
    }
}
