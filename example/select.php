<?php
namespace ngyuki\DoctrineTableGateway\Example;

use Cache\Adapter\Doctrine\DoctrineCachePool;
use Doctrine\Common\Cache\FilesystemCache;
use ngyuki\DoctrineTableGateway\Metadata;
use ngyuki\DoctrineTableGateway\TableGateway;
use ngyuki\DoctrineTableGateway\Test\ConnectionManager;

require __DIR__ . '/bootstrap.php';

$conn = ConnectionManager::getConnection();
$conn->beginTransaction();

$cache = new DoctrineCachePool(new FilesystemCache(__DIR__ . '/../build/'));
$meta = new Metadata($conn, $cache);

$t = (new TableGateway($conn, 't_user', $meta))->scope('aa = 1');

///

h("all()");
$res = $t->all();
dump($res);

///

h("scope(['bb' => 1])->all()");
$res = $t->scope(['bb' => 1])->all();
dump($res);

///

h("find()");
$res = $t->find();
dump($res);

///

h("find(6)");
$res = $t->find(6);
dump($res);


///

h("orderBy('id', 'desc')->all()");
$res = $t->orderBy('id', 'desc')->all();
dump($res);

///

h("all()->asColumn('id')");
$res = $t->all()->asColumn('id');
foreach ($res as $key => $val) {
    dump([$key => $val]);
}

///

h("all()->asColumn('id')");
$res = $t->all()->asUnique('id');
foreach ($res as $key => $val) {
    dump([$key => $val]);
}

///

h("all()->asColumn('id')");
$res = $t->all()->asPair('id', 'name');
foreach ($res as $key => $val) {
    dump([$key => $val]);
}
