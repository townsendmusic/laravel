<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../app/db_connection.php');
require_once('../app/pdo_result.php');
require_once('../app/store_products.php');

$test = new \App\store_products();

$result = $test->sectionProducts($store_id = 3, $section = "ALL", $number = null, $page = null, $sort = 0);
print_r($result);
