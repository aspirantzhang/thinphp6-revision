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
    private $revisionId;
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
        unset($data['id']);
        $this->mainTableData = json_encode($data);
    }

    private function getMainTableData()
    {
        return json_decode($this->mainTableData, true);
    }

    private function setI18nTableData(array $data)
    {
        foreach ($data as &$singleI18nRecord) {
            unset($singleI18nRecord['_id']);
        }
        $this->i18nTableData = json_encode($data);
    }

    private function getI18nTableData()
    {
        return json_decode($this->i18nTableData, true);
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

    private function initRevisionData()
    {
        $revision = Db::table('revision')->where('id', $this->revisionId)->find();
        if ($revision) {
            $this->mainTableData = $revision['main_data'];
            $this->i18nTableData = $revision['i18n_data'];
            return [
                'tableName' => $revision['table_name'],
                'originalId' => $revision['original_id'],
                'title' => $revision['title'],
            ];
        }
        return [];
    }

    private function ifRevisionMathOriginal(array $revisionData): bool
    {
        return ($revisionData['tableName'] === $this->tableName) && ($revisionData['originalId'] === $this->originalId);
    }

    private function updateMainTableData()
    {
        Db::name($this->tableName)->where('id', $this->originalId)->update($this->getMainTableData());
    }

    private function deleteOriginalI18nData()
    {
        Db::table($this->i18nTableName)->where('original_id', $this->originalId)->delete();
    }

    private function insertI18nTableData()
    {
        $num = Db::name($this->i18nTableName)->insertAll($this->getI18nTableData());
        var_dump($num);
    }

    public function restore(int $revisionId)
    {
        $this->revisionId = $revisionId;
        $revisionData = $this->initRevisionData();
        if (false === $this->ifRevisionMathOriginal($revisionData)) {
            throw new Exception("The revision does not match the original record.");
        }
        $this->updateMainTableData();
        $this->deleteOriginalI18nData();
        $this->insertI18nTableData();
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
