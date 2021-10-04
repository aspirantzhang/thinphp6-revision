<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision\traits;

use think\facade\Config;

trait Revision
{
    public function getRevisionTable()
    {
        $this->loadModelConfig();
        return Config::get($this->getModelName() . '.revisionTable') ?: [];
    }
}
