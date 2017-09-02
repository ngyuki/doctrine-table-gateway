<?php
namespace ngyuki\DoctrineTableGateway\Example;

function h($msg)
{
    echo "\n=== $msg\n";
}

function dump($data)
{
    if ($data instanceof \ArrayObject) {
        $data = [$data];
    } elseif ($data instanceof \Traversable == false) {
        $data = [$data];
    }
    foreach ($data as $d) {
        echo (json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT)) . PHP_EOL;
    }
}
