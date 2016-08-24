<?php

namespace Medoo\Console;
use Medoo\Seeds\DatabaseSeeder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Console | RunSeeds
 * */

class RunSeeds extends Command
{

    public function configure()
    {
        $this->setName('seed')->setDescription('Seed the database with records');
    }

    protected function execute(InputInterface $input , OutputInterface $output)
    {
        $seed = new DatabaseSeeder();
        $seed->run();
    }
}