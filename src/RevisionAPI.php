<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision;

use think\facade\Db;

class RevisionAPI
{
    public function listAPI(string $tableName, int $recordId, int $page = 1, int $perPage = 5)
    {
        $data = $this->getListData($tableName, $recordId, $page, $perPage);

        if ($data['total'] === 0) {
            return [
                'success' => false,
                'message' => __('record is empty'),
                'data' => []
            ];
        }

        return [
            'success' => true,
            'message' => '',
            'data' => [
                'dataSource' => $data['dataSource'],
                'meta' => [
                    'total' => $data['total'],
                    'page' => $data['page'],
                ]
            ]
        ];
    }

    private function getListData(string $tableName, int $recordId, int $page, int $perPage)
    {
        if (empty($tableName) || empty($recordId)) {
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

        return [
            'dataSource' => $list['data'] ?? $list['dataSource'] ?? [],
            'total' => $list['total'] ?? $list['pagination']['total'] ?? 0,
            'page' => $list['current_page'] ?? $list['pagination']['page'] ?? 1,
        ];
    }
}
