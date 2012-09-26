<?php if (!defined('AVRELIA')) die('Access is denied!');

class Migrate_Cli
{
    # Migrate model
    protected $model;

    public function __construct()
    {
        $this->model = new Migrate; 

        // Subscribe to successful events...
        Event::watch('/plug/avrelia/migrate/to/success', function($message) {
            Dot::ok($message);
        });

        // Subscribe to successful events...
        Event::watch('/plug/avrelia/migrate', function($message) {
            Dot::inf($message);
        });
    }

    public function action_none()
        { $this->action_migrate(); }

    public function action_help()
    {
        Dot::doc(
            'Migration, database migrations engine',
            'Usage: migration [option]',
            array(
                'migrate'      => 'Migrate to the latest version',
                'to <version>' => 'Migrate to particular version',
                'up'           => 'One version up, is possible',
                'down'         => 'One version down, if possible',
                'create'       => 'Create a new migration',
                'current'      => 'Display current version',
                'latest'       => 'Display latest version',
            )
        );
    }

    public function action_migrate()
    {
        try {
            $this->model->migrate();
        }  catch(\Avrelia\Exception\Database $e) {
            Dot::err($e->getMessage());
        }

        $this->action_current();
    }

    public function action_up()
    {
        try {
            $this->model->up();
        }  catch(\Avrelia\Exception\Database $e) {
            Dot::err($e->getMessage());
        }

        $this->action_current();
    }

    public function action_down()
    {
        try {
            $this->model->down();
        }  catch(\Avrelia\Exception\Database $e) {
            Dot::err($e->getMessage());
        }

        $this->action_current();
    }

    public function action_to($version=false)
    {
        try {
            if ($this->model->to((int)$version)) {
                $this->action_current();
            }
        } catch(\Avrelia\Exception\Database $e) {
            Dot::err($e->getMessage());
        }
    }

    public function action_create()
    {
        Dot::inf('  Please enter migration name, or pres enter to leave it empty:');
        $input = Dot::input('  ', function($input, &$in) {
            if ($in['line'] > 0) {
                if ($in['line'] === 1) {
                    Dot::nl();
                    Dot::inf('  Please enter tasks, type `done` when finished...');
                    $in['title'] = '  ...task: ';
                }

                if ($input === 'done') { return false; }
            }

            return true;
        });

        $name  = $input['inputs'][0] or null;
        $tasks = array_slice($input['inputs'], 1, -1);

        return $this->model->create($tasks, $name);
    }

    public function action_current()
        { Dot::inf('Current version is: ' . $this->model->get_current()); }

    public function action_latest()
        { Dot::inf('Laters version is: ' . $this->model->get_latest()); }
}