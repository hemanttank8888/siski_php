<?php
require_once 'susky_spider.php';

// Main program
$spider = new SuskiSpider();
$spider->startRequests();
$spider->saveDataToFile();
?>

