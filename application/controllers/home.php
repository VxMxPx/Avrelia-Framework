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
}
