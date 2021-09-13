<?php

declare(strict_types=1);

namespace aspirantzhang\octopusRevision;

use think\facade\Db;

class RevisionAPITest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        Db::execute('DROP TABLE IF EXISTS `revision`');
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

    public function testRevisionAPIDefaultPage()
    {
        $result = (new RevisionAPI())->listAPI('revision_test', 100);
        $this->assertStringStartsWith('{"success":true,"message":"","data":{"dataSource":[{"id":20,"table_name":"revision_test","original_id":100,"title":"revision_test_19"', json_encode($result));
    }

    public function testRevisionAPISpecificPageAndPerPage()
    {
        $result = (new RevisionAPI())->listAPI('revision_test', 100, 3, 2);
        $this->assertEquals(count($result['data']['dataSource']), 2);
        $this->assertStringStartsWith('{"success":true,"message":"","data":{"dataSource":[{"id":16', json_encode($result));
    }

    public function testRevisionAPIEmptyRecord()
    {
        $result = (new RevisionAPI())->listAPI('revision_test', 999);
        $this->assertStringStartsWith('{"success":false,"message":"record is empty","data":[]}', json_encode($result));
    }
}
