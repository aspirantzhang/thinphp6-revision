<?php

include __DIR__ . '/../vendor/autoload.php';
use think\facade\Db;

Db::setConfig([
    // 默认数据连接标识
    'default'     => 'mysql',
    // 数据库连接信息
    'connections' => [
        'mysql' => [
            // 数据库类型
            'type'     => 'mysql',
            // 主机地址
            'hostname' => '127.0.0.1',
            // 数据库名
            'database' => 'revision',
            // 用户名
            'username' => 'root',
            // 密码
            'password' => 'dbpass',
            // 数据库编码默认采用utf8
            'charset'  => 'utf8',
            // 数据库表前缀
            'prefix'   => '',
            // 是否需要断线重连
            'break_reconnect' => false,
            // 断线标识字符串
            'break_match_str' => [],
            // 数据库调试模式
            'debug'    => false,
        ],
    ],
]);
