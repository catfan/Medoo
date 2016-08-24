<?php

namespace Medoo\Console;

use Medoo\Foundation\DataBase as DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Console | TestConnection
 * */

class TestConnection extends Command
{
    public function configure()
    {
        /**
         * configure command
         * */
        $this->setName('medoo:test')
             ->setDescription('Test medoo connection');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * execute command in console with symfony
         * */
        if(new DB){
            $SymfonyStyle = new SymfonyStyle($input, $output);
            $SymfonyStyle->success([
                'Connected to "' . config('name','medoo') . '" database ✅ '
            ]);
        }
    }
}