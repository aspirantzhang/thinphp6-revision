<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision;

use think\facade\Db;

class RevisionTest extends TestCase
{
    private static $restoreRecordId;
    private static $restoreRevisionId;
    private static $extraRevisionId;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $time = '2001-01-01 01:01:01';
        // add
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
        // restore
        self::$restoreRecordId = Db::name('user')->insertGetId([
            'username' => 'not restore',
            'create_time' => $time,
            'update_time' => $time,
        ]);
        Db::name('user_i18n')->insertAll(
            [
                [
                    'original_id' => self::$restoreRecordId,
                    'display_name' => 'not restore',
                    'lang_code' => 'en-us',
                    'translate_time' => $time,
                ],
                [
                    'original_id' => self::$restoreRecordId,
                    'display_name' => '没有恢复',
                    'lang_code' => 'zh-cn',
                    'translate_time' => $time,
                ],
            ]
        );
        self::$restoreRevisionId = Db::name('revision')->insertGetId([
            'table_name' => 'user',
            'original_id' => self::$restoreRecordId,
            'title' => 'restore unit test',
            'main_data' => '{"username":"restore-test","create_time":"2001-01-01 01:01:01","update_time":"2001-01-01 01:01:01","delete_time":null,"status":1}',
            'i18n_data' => '[{"original_id":' . self::$restoreRecordId . ',"lang_code":"en-us","display_name":"Restore Test","translate_time":"2001-01-01 01:01:01"},{"original_id":' . self::$restoreRecordId . ',"lang_code":"zh-cn","display_name":"\u6062\u590d\u6d4b\u8bd5","translate_time":"2001-01-01 01:01:01"}]',
            'extra_data' => '[]',
            'create_time' => $time,
            'update_time' => $time,
        ]);
        // extra
        self::$extraRevisionId = Db::name('user')->insertGetId([
            'username' => 'extra-test',
            'create_time' => $time,
            'update_time' => $time,
        ]);
        Db::name('user_group')->insertAll(
            [
                [
                    'user_id' => self::$extraRevisionId,
                    'group_id' => 1,
                ],
                [
                    'user_id' => self::$extraRevisionId,
                    'group_id' => 2,
                ],
            ]
        );
        Db::name('user_profile')->insertAll(
            [
                [
                    'user_key' => self::$extraRevisionId,
                    'group_id' => 1,
                ],
            ]
        );
    }

    public function testAddSuccessfully()
    {
        $revision = new Revision('user', 1);
        $revisionId = $revision->add('unit test');
        $revisionRecord = Db::table('revision')->where('id', $revisionId)->find();
        $this->assertEquals('user', $revisionRecord['table_name']);
        $this->assertEquals(1, $revisionRecord['original_id']);
        $this->assertEquals('unit test', $revisionRecord['title']);
        $this->assertEquals('{"username":"unit-test","create_time":"2001-01-01 01:01:01","update_time":"2001-01-01 01:01:01","delete_time":null,"status":1}', $revisionRecord['main_data']);
        $this->assertEquals('[{"original_id":1,"lang_code":"en-us","display_name":"Unit Test","translate_time":"2001-01-01 01:01:01"},{"original_id":1,"lang_code":"zh-cn","display_name":"\u5355\u5143\u6d4b\u8bd5","translate_time":"2001-01-01 01:01:01"}]', $revisionRecord['i18n_data']);
        return (int)$revisionId;
    }

    public function testRestoreSuccessfully()
    {
        $revision = new Revision('user', (int)self::$restoreRecordId);
        $revision->restore((int)self::$restoreRevisionId);
        $restoreRecord = Db::table('user')->where('id', self::$restoreRecordId)->find();
        $this->assertEquals('restore-test', $restoreRecord['username']);
        $restoreI18nRecord = Db::table('user_i18n')->where('original_id', self::$restoreRecordId)->select();
        $this->assertEquals('[{"_id":5,"original_id":2,"lang_code":"en-us","display_name":"Restore Test","translate_time":"2001-01-01 01:01:01"},{"_id":6,"original_id":2,"lang_code":"zh-cn","display_name":"\u6062\u590d\u6d4b\u8bd5","translate_time":"2001-01-01 01:01:01"}]', json_encode($restoreI18nRecord));
    }

    public function testRevisionHasExtraWithIndexedArray()
    {
        $revision = new Revision('user', (int)self::$extraRevisionId, ['user_group']);
        $revisionId = $revision->add('extra indexed');
        $record = Db::table('revision')->where('id', $revisionId)->find();
        $this->assertEquals('extra indexed', $record['title']);
        $this->assertEquals('{"username":"extra-test","create_time":"2001-01-01 01:01:01","update_time":"2001-01-01 01:01:01","delete_time":null,"status":1}', $record['main_data']);
        $this->assertEquals('{"user_group":[{"user_id":3,"group_id":1},{"user_id":3,"group_id":2}]}', $record['extra_data']);
    }

    public function testRevisionHasExtraWithAssociativeArray()
    {
        $revision = new Revision('user', (int)self::$extraRevisionId, ['user_profile' => 'user_key', 'user_group' => 'user_id']);
        $revisionId = $revision->add('extra associative');
        $record = Db::table('revision')->where('id', $revisionId)->find();
        $this->assertEquals('extra associative', $record['title']);
        $this->assertEquals('{"username":"extra-test","create_time":"2001-01-01 01:01:01","update_time":"2001-01-01 01:01:01","delete_time":null,"status":1}', $record['main_data']);
        $this->assertEquals('{"user_profile":[{"user_key":3,"group_id":1}],"user_group":[{"user_id":3,"group_id":1},{"user_id":3,"group_id":2}]}', $record['extra_data']);
    }
}
