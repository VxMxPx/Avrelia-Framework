<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

class homeController
{
	/**
	 * Default Controller's Action
	 *
	 * @return void
	 */
	public function index()
	{
		# Add jQuery
		cJquery::Add();

		# Set variable
		View::AddVar('greeting', Model::Get('home')->sayHello());

		# Get Master template (always first!)
		View::Get('master')->asMaster();

		# Get master's region
		View::Get('home')->asRegion('main');
	}
	//-

	/**
	 * For Ajax Request...
	 */
	public function greeting()
	{
		View::Get('simple', array(
			'data' => Model::Get('home')->sayHello(),
		));
	}
	//-

	/**
	 * Not found!
	 */
	public function not_found_404()
	{
		HTTP::Status404_NotFound('<h1>404: File not found!</h1>');
	}
	//-
}
//--
