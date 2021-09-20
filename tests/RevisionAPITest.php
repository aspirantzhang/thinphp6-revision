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
                'table_name' => 'user',
                'original_id' => 100,
                'title' => 'user_' . $i,
                'main_data' => '[]',
                'i18n_data' => '[]',
                'extra_data' => '[]',
                'create_time' => $time,
                'update_time' => $time,
            ];
        }
        Db::name('revision')->insertAll($moreRecords);
    }

    public function testSaveAPI()
    {
        $time = date('Y-m-d H:i:s');
        $addRecordId = Db::name('user')->insertGetId([
            'username' => 'unit-test',
            'create_time' => $time,
            'update_time' => $time,
        ]);
        Db::name('user_i18n')->insertAll(
            [
                [
                    'original_id' => $addRecordId,
                    'display_name' => 'Unit Test',
                    'lang_code' => 'en-us',
                    'translate_time' => $time,
                ],
                [
                    'original_id' => $addRecordId,
                    'display_name' => '单元测试',
                    'lang_code' => 'zh-cn',
                    'translate_time' => $time,
                ],
            ]
        );
        $revisionId = (new RevisionAPI())->saveAPI('save test', 'user', (int)$addRecordId);
        $result = Db::table('revision')->where('id', $revisionId)->find();
        $this->assertEquals('user', $result['table_name']);
        $this->assertEquals('save test', $result['title']);
        $this->assertStringStartsWith('{"username":"unit-test",', $result['main_data']);
        $this->assertStringStartsWith('[{"original_id":' . $addRecordId . ',"lang_code":"en-us"', $result['i18n_data']);
    }

    public function testListAPIDefaultPage()
    {
        $result = (new RevisionAPI())->listAPI('user', 100);
        $this->assertStringStartsWith('{"success":true,"message":"","data":{"dataSource":[{"id":20,"table_name":"user","original_id":100,"title":"user_19"', json_encode($result));
    }

    public function testListAPISpecificPageAndPerPage()
    {
        $result = (new RevisionAPI())->listAPI('user', 100, 3, 2);
        $this->assertEquals(count($result['data']['dataSource']), 2);
        $this->assertStringStartsWith('{"success":true,"message":"","data":{"dataSource":[{"id":16', json_encode($result));
    }

    public function testListAPIEmptyRecord()
    {
        $result = (new RevisionAPI())->listAPI('user', 999);
        $this->assertStringStartsWith('{"success":false,"message":"record is empty","data":[]}', json_encode($result));
    }

    public function testRestoreAPI()
    {
        // insert main table
        $time = '2001-01-01 01:01:01';
        $mainId = Db::name('user')->insertGetId([
            'username' => 'not restore',
            'create_time' => $time,
            'update_time' => $time,
        ]);
        $revisionId = Db::name('revision')->insertGetId([
            'table_name' => 'user',
            'original_id' => $mainId,
            'title' => 'restore unit test',
            'main_data' => '{"username":"RestoreAPI","create_time":"2001-01-01 01:01:01","update_time":"2001-01-01 01:01:01","delete_time":null,"status":1}',
            'i18n_data' => '[{"original_id":' . $mainId . ',"lang_code":"en-us","display_name":"Restore Test","translate_time":"2001-01-01 01:01:01"},{"original_id":' . $mainId . ',"lang_code":"zh-cn","display_name":"\u6062\u590d\u6d4b\u8bd5","translate_time":"2001-01-01 01:01:01"}]',
            'extra_data' => '[]',
            'create_time' => $time,
            'update_time' => $time,
        ]);

        $result = (new RevisionAPI())->restoreAPI('user', (int)$mainId, (int)$revisionId);
        $this->assertEquals('{"success":true,"message":"revision restore successfully","data":[]}', json_encode($result));
    }

    public function testRestoreAPIWithExtraTable()
    {
        // insert main table
        $time = '2001-01-01 01:01:01';
        $mainId = Db::name('user')->insertGetId([
            'id' => 110,
            'username' => 'not restore with extra',
            'create_time' => $time,
            'update_time' => $time,
        ]);
        $revisionId = Db::name('revision')->insertGetId([
            'table_name' => 'user',
            'original_id' => $mainId,
            'title' => 'restore with extra',
            'main_data' => '{"username":"RestoreWithExtra","create_time":"2001-01-01 01:01:01","update_time":"2001-01-01 01:01:01","delete_time":null,"status":1}',
            'i18n_data' => '[]',
            'extra_data' => '{"user_profile":[{"user_key":' . $mainId . ',"group_id":1}],"user_group":[{"user_id":' . $mainId . ',"group_id":1},{"user_id":' . $mainId . ',"group_id":2}]}',
            'create_time' => $time,
            'update_time' => $time,
        ]);

        $result = (new RevisionAPI())->restoreAPI('user', (int)$mainId, (int)$revisionId, ['user_group' => 'user_id', 'user_profile' => 'user_key']);
        $this->assertEquals('{"success":true,"message":"revision restore successfully","data":[]}', json_encode($result));
        $groupRecord = Db::table('user_group')->where('user_id', $mainId)->select()->toArray();
        $this->assertEquals($groupRecord[0]['group_id'], 1);
        $this->assertEquals($groupRecord[1]['group_id'], 2);
        $profileRecord = Db::table('user_profile')->where('user_key', $mainId)->select()->toArray();
        $this->assertEquals($profileRecord[0]['group_id'], 1);
    }

    public function testReadAPISuccessfully()
    {
        $actual = (new RevisionAPI())->readAPI(1);
        $this->assertEquals('{"success":true,"message":"","data":{"dataSource":{"id":1,"table_name":"user","original_id":100,"title":"user_0","main_data":"[]","i18n_data":"[]","extra_data":"[]","create_time":"2001-01-01 01:01:01","update_time":"2001-01-01 01:01:01","delete_time":null,"status":1}}}', json_encode($actual));
    }

    public function testReadAPIFailed()
    {
        $actual = (new RevisionAPI())->readAPI(0);
        $this->assertEquals('{"success":false,"message":"get revision data failed","data":[]}', json_encode($actual));
    }
}
