<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

class Home_Controller
{
    # Default Action
    public function index()
    {
        # Get Master template
        View::get('master')->as_master();

        # Get master's region
        View::get('home')->as_region('main');
    }

    # For Ajax Request...
    public function greeting()
    {
        $Model = new Home_Model();

        View::get('simple', array(
            'data' => $Model->sayHello(),
        ));
    }

    # Not found!
    public function not_found_404()
    {
        Http::status_404_not_found('<h1>404: Not found!</h1>');
    }
}
