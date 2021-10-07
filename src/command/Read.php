<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use aspirantzhang\octopusRevision\RevisionAPI;

class Read extends Command
{
    protected function configure()
    {
        $this->setName('revision:read')
        ->addArgument('revisionId', Argument::REQUIRED, "Revision id")
        ->setDescription('View the details of a revision');
    }

    protected function execute(Input $input, Output $output)
    {
        $revisionId = trim($input->getArgument('revisionId'));

        $result = (new RevisionAPI())->readAPI((int)$revisionId);
        if ($result['success'] === true) {
            $output->writeln('<comment>' . print_r($result['data']['dataSource']) . '</comment>');
        } else {
            $output->writeln('<error>' . $result['message'] . '</error>');
        }
    }
}
