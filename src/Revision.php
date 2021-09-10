<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision;

use think\facade\Db;
use think\Exception;

class Revision
{
    private $tableName;
    private $i18nTableName;
    private $originalId;
    private $mainTableData;
    private $i18nTableData;

    public function __construct(string $tableName, int $originalId)
    {
        $this->tableName = $tableName;
        $this->i18nTableName = $tableName . '_i18n';
        $this->originalId = (int)$originalId;
        $this->mainTableData = '[]';
        $this->i18nTableData = '[]';
    }

    private function setMainTableData(array $data)
    {
        $this->mainTableData = json_encode($data);
    }

    private function setI18nTableData(array $data)
    {
        $this->i18nTableData = json_encode($data);
    }

    private function i18nTableExists(): bool
    {
        return $this->tableExists($this->i18nTableName);
    }

    private function setTableData(): void
    {
        $record = Db::table($this->tableName)->where('id', $this->originalId)->find();
        $this->setMainTableData($record);

        if ($this->i18nTableExists()) {
            $i18nRecord = Db::table($this->i18nTableName)->where('original_id', $this->originalId)->select()->toArray();
            $this->setI18nTableData($i18nRecord);
        }
    }

    private function saveRevision(string $title)
    {
        $currentTime = date('Y-m-d H:i:s');
        $data = [
            'table_name' => $this->tableName,
            'original_id' => $this->originalId,
            'title' => $title,
            'main_data' => $this->mainTableData,
            'i18n_data' => $this->i18nTableData,
            'create_time' => $currentTime,
            'update_time' => $currentTime
        ];
        return Db::name('revision')->insertGetId($data);
    }

    public function add(string $title)
    {
        $this->setTableData();
        $revisionId = $this->saveRevision($title);
        return $revisionId;
    }

    private function getAllColumnNamesWithoutId(array $record): array
    {
        $names = array_keys($record);
        return array_diff($names, ['id', '_id']);
    }

    private function tableExists(string $tableName): bool
    {
        try {
            Db::query("select 1 from `$tableName` LIMIT 1");
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
