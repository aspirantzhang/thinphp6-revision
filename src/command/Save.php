<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Exception;
use aspirantzhang\octopusRevision\RevisionAPI;

class Save extends Command
{
    protected function configure()
    {
        $this->setName('revision:save')
            ->addArgument('revisionTitle', Argument::REQUIRED, "Revision title")
            ->addArgument('tableName', Argument::REQUIRED, "Table name")
            ->addArgument('originalId', Argument::REQUIRED, "Original id")
            ->setDescription('Save a revision of a specific record of a table');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>Processing...</info>');

        $revisionTitle = trim($input->getArgument('revisionTitle'));
        $tableName = trim($input->getArgument('tableName'));
        $originalId = trim($input->getArgument('originalId'));

        try {
            $revisionId = (new RevisionAPI())->saveAPI($revisionTitle, $tableName, (int)$originalId);
            $output->writeln('<comment>Revision ID: ' . $revisionId . '</comment>');
            $output->writeln('<info>...Complete successfully.</info>');
        } catch (Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
        }
    }
}
