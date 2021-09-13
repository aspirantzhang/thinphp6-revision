<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision;

use think\facade\Db;

class RevisionQuery
{
    public function query(int $page = 1, $perPage = 5)
    {
        $list = Db::name('revision')->where('status', 1)->order('id', 'desc')->paginate([
            'list_rows' => $perPage,
            'page' => $page
        ])->toArray();
        return $list['data'] ?? [];
    }
}
