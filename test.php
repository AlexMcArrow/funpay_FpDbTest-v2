<?php

use FpDbTest\Database;
use FpDbTest\DatabaseTest;

spl_autoload_register(function ($class) {
    $a = array_slice(explode('\\', $class), 1);
    if (!$a) {
        throw new Exception();
    }
    $filename = implode('/', [__DIR__, ...$a]) . '.php';
    require_once $filename;
});

$mysqli = @new mysqli('10.10.0.20', 'sudo', 'sudo', 'test', 3306);
if ($mysqli->connect_errno) {
    throw new Exception($mysqli->connect_error);
}

$db = new Database($mysqli);
$db->_set_debug();
$test = new DatabaseTest($db);
$test->testBuildQuery();

exit('OK');
