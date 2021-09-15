<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision;

use think\facade\Db;

class RevisionAPITest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $time = '2001-01-01 01:01:01';
        $moreRecords = [];
        for ($i = 0; $i < 20; $i++) {
            $moreRecords[] = [
                'table_name' => 'revision_test',
                'original_id' => 100,
                'title' => 'revision_test_' . $i,
                'main_data' => '[]',
                'i18n_data' => '[]',
                'create_time' => $time,
                'update_time' => $time,
            ];
        }
        Db::name('revision')->insertAll($moreRecords);
    }

    public function testListAPIDefaultPage()
    {
        $result = (new RevisionAPI())->listAPI('revision_test', 100);
        $this->assertStringStartsWith('{"success":true,"message":"","data":{"dataSource":[{"id":20,"table_name":"revision_test","original_id":100,"title":"revision_test_19"', json_encode($result));
    }

    public function testListAPISpecificPageAndPerPage()
    {
        $result = (new RevisionAPI())->listAPI('revision_test', 100, 3, 2);
        $this->assertEquals(count($result['data']['dataSource']), 2);
        $this->assertStringStartsWith('{"success":true,"message":"","data":{"dataSource":[{"id":16', json_encode($result));
    }

    public function testListAPIEmptyRecord()
    {
        $result = (new RevisionAPI())->listAPI('revision_test', 999);
        $this->assertStringStartsWith('{"success":false,"message":"record is empty","data":[]}', json_encode($result));
    }

    public function testRestoreAPI()
    {
        // insert main table
        $time = '2001-01-01 01:01:01';
        $mainId = Db::name('revision_test')->insertGetId([
            'username' => 'not restore',
            'create_time' => $time,
            'update_time' => $time,
        ]);
        $revisionId = Db::name('revision')->insertGetId([
            'table_name' => 'revision_test',
            'original_id' => $mainId,
            'title' => 'restore unit test',
            'main_data' => '{"username":"RestoreAPI","create_time":"2001-01-01 01:01:01","update_time":"2001-01-01 01:01:01","delete_time":null,"status":1}',
            'i18n_data' => '[{"original_id":' . $mainId . ',"lang_code":"en-us","display_name":"Restore Test","translate_time":"2001-01-01 01:01:01"},{"original_id":' . $mainId . ',"lang_code":"zh-cn","display_name":"\u6062\u590d\u6d4b\u8bd5","translate_time":"2001-01-01 01:01:01"}]',
            'create_time' => $time,
            'update_time' => $time,
        ]);

        $result = (new RevisionAPI())->restoreAPI('revision_test', (int)$mainId, (int)$revisionId);
        $this->assertEquals('{"success":true,"message":"revision restore successfully","data":[]}', json_encode($result));
    }
}
