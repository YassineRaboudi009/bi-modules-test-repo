<?php

$basePath = $basePath ?? $_ENV['PWD'];

if(file_exists($autoload =  __DIR__ . '/vendor/autoload.php')) {
    $basePath = __DIR__;
    require_once $autoload;
}

$tester = new \BettIngenieure\Tester\PhpTester(
    $basePath,
    $argv[1] ?? null,
);
$tester->run();