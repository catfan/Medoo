<?php

namespace Medoo\Console;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Console | RunMigrations
 * */

class RunMigrations extends Command
{

    public function configure()
    {
        $this->setName('migrate')
             ->setDescription('Run all migrations')
             ->addOption('migration','m', InputOption::VALUE_REQUIRED,'call one migration');
    }

    protected function execute(InputInterface $input , OutputInterface $output)
    {
        if($input->getOption('migration')){
            $class = "Medoo\\Migrations\\".$input->getOption('migration');
            $migration = new $class;
            $migration->up();
        } else {
            $migrations = $this->getMigrations();
            $i = 0;
            while ($i <= count($migrations) - 1){
                $newclass = "Medoo\\Migrations\\".$migrations[$i];
                $class = new $newclass();
                $class->up();
                $i++;
            }
        }
    }

    private function getMigrations()
    {
        $Migrations = scandir(__DIR__."/../../database/migrations/");
        $i = 0;
        while ($i <= count($Migrations) - 1){
            if($Migrations[$i] == '.' || $Migrations[$i] == '..'){
                unset($Migrations[$i]);
            }
            $i++;
        }
        sort($Migrations);
        $b = 0; $classes = [];
        while ($b <= count($Migrations) - 1){
            $file = file_get_contents(__DIR__."/../../database/migrations/".$Migrations[$b]);
            $length = strpos($file,"class ");
            $fileAnalyts = substr($file,$length);
            $findcol = strpos($fileAnalyts,"extends");
            $class = substr($fileAnalyts,0,$findcol);
            $className = str_replace('class ','',trim($class));
            array_push($classes,trim($className));
            $b++;
        }
        return $classes;
    }
}