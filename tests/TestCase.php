<?php

namespace aspirantzhang\octopusRevision;

use think\facade\Db;
use Mockery as m;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{

    public static function setUpBeforeClass(): void
    {
        Db::execute('DROP TABLE IF EXISTS `revision`, `user`, `user_i18n`');
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
CREATE TABLE `user` (
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
CREATE TABLE `user_i18n` (
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
    }
    protected function setUp(): void
    {
        $langMock = m::mock('alias:think\facade\Lang');
        $langMock->shouldReceive('get')->andReturnUsing(function (string $name, array $vars = [], string $lang = '') {
            if (!empty($vars)) {
                return $name . ': ' . implode(';', array_map(function ($key, $value) {
                    return $key . '=' . $value;
                }, array_keys($vars), $vars));
            }
            return $name;
        });
        $langMock->shouldReceive('getLangSet')->andReturn('en-us');
    }

    protected function tearDown(): void
    {
    }
}
