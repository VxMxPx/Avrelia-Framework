<?php if (!defined('AVRELIA')) die('Access is denied!');

class Migrate_Cli
{
    # Migrate model
    protected $model;

    public function __construct()
    {
        $this->model = new Migrate;
    }

    public function action_none()
    {
        $this->action_help();
    }

    public function action_help()
    {
        Dot::doc(
            'Migration, database migrations engine',
            'Usage: migration [option]',
            array(
                'migrate'      => 'Migrate to the latest version',
                'to <version>' => 'Migrate to particualt version',
                'up'           => 'One version up, is possible',
                'down'         => 'One version down, if possilbe',
                'create'       => 'Create a new migration',
                'current'      => 'Dispay current version',
                'latest'       => 'Display latest version',
            )
        );
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
    {
        Dot::inf('Current version is: ' . $this->model->get_current());
    }

    public function action_latest()
    {
        Dot::inf('Laters version is: ' . $this->model->get_latest());
    }
}