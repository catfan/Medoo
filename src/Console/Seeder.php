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
 * @package Medoo Console | Seeder
 * */

class Seeder extends Command
{

    public function configure()
    {
        $this->setName('make:seeder')->setDescription('Make new seeder');
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the seeder.');
    }

    protected function execute(InputInterface $input , OutputInterface $output)
    {
        $command = new MedooStyle($input,$output);
        $dir = __DIR__."/../../database/seeds/";
        if(!is_dir($dir)){
            mkdir($dir,0777);
            copy(
                __DIR__."/../Foundation/files/DatabaseSeeder.stub",
                $dir."/DatabaseSeeder.php"
            );
        }
        $name = $input->getArgument('name');
        if($this->create_seeder($dir,$name)){
            exec('composer dumpautoload');
            $command->info('Seeder has been created successfully');
            return true;
        }
        $command->error('database/seeds/directory was not found');
        return false;
    }

    public function create_seeder($dir,$name)
    {
        $file = $dir . "/" . $name . ".php";
        $isSeeder = $this->isSeeder($name);
        if (!$isSeeder) {
            $Dub = file_get_contents(__DIR__ . '/stubs/seeder.stub');
            $dumyClassname = $this->generateDumyName($name);
            $seeder = str_replace('DummyClass',$dumyClassname,$Dub);
            file_put_contents($file, $seeder);
            return true;
        }
        return false;
    }

    public function isSeeder($name)
    {
        if(!class_exists($name)){
            return false;
        }
        return true;
    }

    private function generateDumyName($name)
    {
        $find = [
            '_','-',',','Select','All','{','}','[',']'
        ];
        return str_replace($find,'',$name);
    }
}
