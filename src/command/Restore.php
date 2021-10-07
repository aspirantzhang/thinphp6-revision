<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Exception;
use aspirantzhang\octopusRevision\RevisionAPI;

class Restore extends Command
{
    protected function configure()
    {
        $this->setName('revision:restore')
            ->addArgument('tableName', Argument::REQUIRED, "Table name")
            ->addArgument('originalId', Argument::REQUIRED, "Original Id")
            ->addArgument('revisionId', Argument::REQUIRED, "Revision id")
            ->setDescription('Restore a record from a specific revision of a table');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>Processing...</info>');

        $tableName = trim($input->getArgument('tableName'));
        $originalId = trim($input->getArgument('originalId'));
        $revisionId = trim($input->getArgument('revisionId'));

        try {
            (new RevisionAPI())->restoreAPI($tableName, (int)$originalId, (int)$revisionId);
            $output->writeln('<info>...Complete successfully.</info>');
        } catch (Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
        }
    }
}
