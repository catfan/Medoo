<?php

namespace Medoo\Console;
use Medoo\Console\ConsoleStyle as MedooStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Console | Migration
 * */

class Migration extends Command
{
    protected $find = [
        'create','table','Migration','migration',
        'Table','Create','column','medoo','from',
        '_','-',',','Select','All','{','}','[',']'
    ];

    public function configure()
    {
        $this->setName('make:migration')->setDescription('Make new migration');
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the migration.');
    }

    protected function execute(InputInterface $input , OutputInterface $output)
    {
        $command = new MedooStyle($input,$output);
        $dir = __DIR__."/../../database/migrations/";
        $name = $input->getArgument('name');
        if(!is_dir($dir)){
            mkdir($dir,0777);
        }
        if($this->create_migration($dir,$name)){
            exec('composer dumpautoload');
            $command->info('Migration has been created successfully');
            return true;
        }
        $command->error($name . ' migration has exits !');
        return false;
    }

    public function create_migration($dir,$name)
    {
        date_default_timezone_set('Asia/Tehran');
        $file = $dir . "/" . $name . "_" . date('Y_m_d_h_i') . ".php";
        if (!$this->isMigration($name)) {
            $Dub = file_get_contents(__DIR__ . '/stubs/migration.stub');
            $dumyName = $this->generateDumyName($name);
            $Migration = str_replace('DummyDate', date('Y/m/d h:i A'), str_replace(
                'DummyClass', $this->generateDumyClassName($name),
                str_replace('DummyName', $dumyName, $Dub)
            ));
            file_put_contents($file, $Migration);
            return true;
        }
        return false;
    }

    public function isMigration($name)
    {
        $class = $this->generateDumyClassName($name);
        if(!class_exists($class)){
            return false;
        }
        return true;
    }

    private function generateDumyClassName($name)
    {
        return "Create".ucwords($this->generateDumyName($name))."Table";
    }

    private function generateDumyName($name)
    {
        return str_replace($this->find,'',$name);
    }
}
