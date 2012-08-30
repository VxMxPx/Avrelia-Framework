<?php if (!defined('AVRELIA')) { die('Access is denied!'); }

class homeController
{
    /**
     * Default Action
     * --
     * @return  void
     */
    public function index()
    {
        # Add jQuery
        cJquery::Add();

        # Set variable
        View::assign(
            'greeting', 
            '<span class="fade">Hello from</span> Avrelia Framework');

        # Get Master template
        View::get('master')->as_master();

        # Get master's region
        View::get('home')->as_region('main');
    }
    //-

    /**
     * For Ajax Request...
     */
    public function greeting()
    {
        $Model = new homeModel();

        View::get('simple', array(
            'data' => $Model->sayHello(),
        ));
    }
    //-

    /**
     * Not found!
     */
    public function not_found_404()
    {
        Http::status_404_not_found('<h1>404: Not found!</h1>');
    }
    //-
}
//--
