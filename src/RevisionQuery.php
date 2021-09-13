<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision;

use think\facade\Db;

class RevisionQuery
{
    public function query(string $tableName, int $recordId, int $page = 1, $perPage = 5)
    {
        if (empty($tableName) && empty($recordId)) {
            throw new \InvalidArgumentException('Table name and record id should not be empty.');
        }
        $list = Db::name('revision')
            ->where('table_name', $tableName)
            ->where('original_id', $recordId)
            ->where('status', 1)
            ->order('id', 'desc')
            ->paginate([
                'list_rows' => $perPage,
                'page' => $page
            ])->toArray();
        return $list['data'] ?? [];
    }
}
