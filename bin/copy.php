#!/usr/bin/env php
<?php

require_once __DIR__ . '/../src/functions.php';


function main($argc, $argv)
{
    try {
        $args = validateArguments($argc, $argv);
        $prodPdo = dbConnection('prod');
        $devPdo = dbConnection('dev');
        copyEntries($args, $prodPdo, $devPdo);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

main($argc, $argv);
