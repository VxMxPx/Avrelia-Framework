<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

/**
 * Avrelia
 * ----
 * Application's General Model
 * ----
 * @package    Avrelia
 * @author     Avrelia.com
 * @copyright  Copyright (c) 2010, Avrelia.com
 * @license    http://avrelia.com/license
 * @link       http://avrelia.com
 * @since      Version 0.80
 * @since      Date tor avg 03 22:53:09 2010
 */


class homeModel
{
	# List Of Greetings
	private $GreetingsList = array(
	'Ahoy!', 'G\'day!', 'Greetings!', 'Hello!', 'Hello there!',
	'Hey!', 'Hi!', 'Hi there!', 'How are you?', 'How are you doing?',
	'How\'s it going?', 'Howdy!', 'Salutations!', 'What\'s up?', 'Yo!',
	);


	/**
	 * Say Hello - Sample Function...
	 *
	 * @return string
	 */
	public function sayHello()
	{
		return $this->GreetingsList[rand(0,count($this->GreetingsList)-1)];
	}
	//-

}
//--
