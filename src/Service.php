<?php

namespace aspirantzhang\octopusRevision;

class Service extends \think\Service
{
    public function boot()
    {
        $this->app->bind('revision', RevisionAPI::class);
        $this->commands([
            'revision:save' => command\Save::class,
        ]);
    }
}
