<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision;

use think\facade\Db;

class RevisionTest extends TestCase
{
    private static $restoreRecordId;
    private static $restoreRevisionId;

    public static function setUpBeforeClass(): void
    {
        Db::execute('DROP TABLE IF EXISTS `revision`, `revision_test`, `revision_test_i18n`');
        Db::execute(<<<END
CREATE TABLE `revision` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `table_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
 `original_id` int(11) unsigned NOT NULL DEFAULT 0,
 `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
 `main_data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
 `i18n_data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
 `create_time` datetime NOT NULL,
 `update_time` datetime NOT NULL,
 `delete_time` datetime DEFAULT NULL,
 `status` tinyint(1) NOT NULL DEFAULT 1,
 PRIMARY KEY (`id`),
 KEY `table_name` (`table_name`,`original_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
END
        );
        Db::execute(<<<END
CREATE TABLE `revision_test` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
 `create_time` datetime NOT NULL,
 `update_time` datetime NOT NULL,
 `delete_time` datetime DEFAULT NULL,
 `status` tinyint(1) NOT NULL DEFAULT 1,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
END
        );
        Db::execute(<<<END
CREATE TABLE `revision_test_i18n` (
 `_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `original_id` int(11) unsigned NOT NULL,
 `lang_code` char(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
 `display_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
 `translate_time` datetime DEFAULT NULL,
 PRIMARY KEY (`_id`),
 UNIQUE KEY `original_id` (`original_id`,`lang_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
END
        );
        $time = '2001-01-01 01:01:01';
        // add
        $addRecordId = Db::name('revision_test')->insertGetId([
            'username' => 'unit-test',
            'create_time' => $time,
            'update_time' => $time,
        ]);
        Db::name('revision_test_i18n')->insertAll(
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
        self::$restoreRecordId = Db::name('revision_test')->insertGetId([
            'username' => 'not restore',
            'create_time' => $time,
            'update_time' => $time,
        ]);
        Db::name('revision_test_i18n')->insertAll(
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
            'table_name' => 'revision_test',
            'original_id' => self::$restoreRecordId,
            'title' => 'restore unit test',
            'main_data' => '{"username":"restore-test","create_time":"2001-01-01 01:01:01","update_time":"2001-01-01 01:01:01","delete_time":null,"status":1}',
            'i18n_data' => '[{"original_id":' . self::$restoreRecordId . ',"lang_code":"en-us","display_name":"Restore Test","translate_time":"2001-01-01 01:01:01"},{"original_id":' . self::$restoreRecordId . ',"lang_code":"zh-cn","display_name":"\u6062\u590d\u6d4b\u8bd5","translate_time":"2001-01-01 01:01:01"}]',
            'create_time' => $time,
            'update_time' => $time,
        ]);
    }

    public function testAddSuccessfully()
    {
        $revision = new Revision('revision_test', 1);
        $revisionId = $revision->add('unit test');
        $revisionRecord = Db::table('revision')->where('id', $revisionId)->find();
        $this->assertEquals('revision_test', $revisionRecord['table_name']);
        $this->assertEquals(1, $revisionRecord['original_id']);
        $this->assertEquals('unit test', $revisionRecord['title']);
        $this->assertEquals('{"username":"unit-test","create_time":"2001-01-01 01:01:01","update_time":"2001-01-01 01:01:01","delete_time":null,"status":1}', $revisionRecord['main_data']);
        $this->assertEquals('[{"original_id":1,"lang_code":"en-us","display_name":"Unit Test","translate_time":"2001-01-01 01:01:01"},{"original_id":1,"lang_code":"zh-cn","display_name":"\u5355\u5143\u6d4b\u8bd5","translate_time":"2001-01-01 01:01:01"}]', $revisionRecord['i18n_data']);
        return (int)$revisionId;
    }

    public function testRestoreSuccessfully()
    {
        $revision = new Revision('revision_test', (int)self::$restoreRecordId);
        $revision->restore((int)self::$restoreRevisionId);
        $restoreRecord = Db::table('revision_test')->where('id', self::$restoreRecordId)->find();
        $this->assertEquals('restore-test', $restoreRecord['username']);
        $restoreI18nRecord = Db::table('revision_test_i18n')->where('original_id', self::$restoreRecordId)->select();
        $this->assertEquals('[{"_id":5,"original_id":2,"lang_code":"en-us","display_name":"Restore Test","translate_time":"2001-01-01 01:01:01"},{"_id":6,"original_id":2,"lang_code":"zh-cn","display_name":"\u6062\u590d\u6d4b\u8bd5","translate_time":"2001-01-01 01:01:01"}]', json_encode($restoreI18nRecord));
    }
}
