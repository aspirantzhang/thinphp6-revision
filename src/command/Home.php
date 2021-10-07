<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Exception;
use aspirantzhang\octopusRevision\RevisionAPI;

class Home extends Command
{
    protected function configure()
    {
        $this->setName('revision:list')
            ->addArgument('tableName', Argument::REQUIRED, "Table name")
            ->addArgument('recordId', Argument::REQUIRED, "Record Id")
            ->addArgument('page', Argument::OPTIONAL, "Current page number")
            ->addArgument('perPage', Argument::OPTIONAL, "Per page number")
            ->setDescription('Get the revision list of a specific record of a table');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('<info>Processing...</info>');

        $tableName = trim($input->getArgument('tableName'));
        $recordId = trim($input->getArgument('recordId'));
        $page = $input->getArgument('page') ? (int)trim($input->getArgument('page')) : null;
        $perPage = $input->getArgument('perPage') ? (int)trim($input->getArgument('perPage')) : null;

        $result = (new RevisionAPI())->listAPI($tableName, (int)$recordId, $page, $perPage);
        if ($result['success'] === true) {
            $list = $result['data']['dataSource'];
            foreach ($list as $item) {
                $output->writeln('<comment> - [' . $item['id'] . ']' . $item['title'] . ' @ ' . $item['create_time'] . '</comment>');
            }
        } else {
            $output->writeln('<error>' . $result['message'] . '</error>');
        }
    }
}
