<?php
/**
 * Who ?: Chaz6chez
 * How !: 250220719@qq.com
 * Where: http://chaz6chez.top
 * Time : 2019/6/5|14:48
 * What : Creating Fucking Bug For Every Code
 */
# PATH常量
# 程序
define('WORKER_MAN'     , true);
define('DEBUG'          , true);
define('INSTANCES_LIMIT', 20);

# 地址
define('SERVER_NAME'        , 'www.worker.com');
define('SERVER_NAME_DEV'    , '127.0.0.1');
define('SOCKET_NAME_WORKER' , 'http://0.0.0.0:84');
define('SOCKET_NAME_CHAT'   , 'http://0.0.0.0:5252');

# PATH常量
define('FILE_PATH'  , '/Upload');
define('PUBLIC_PATH', ROOT_PATH . '/public');
//define('TMP_PATH'   , SERVER_PATH . '/tmp');
define('UPLOAD_PATH', FILE_PATH . '/upload/');
//define('LOG_PATH'   , SERVER_PATH . '/log');
define('API_PATH'   , ROOT_PATH . '/api');
define('COMMON_PATH', API_PATH . '/Common');

define('BIRTH_TIME' , time());