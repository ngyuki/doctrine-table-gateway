<?php
namespace ngyuki\DoctrineTableGateway\Example;

use ngyuki\DoctrineTableGateway\Test\ConnectionManager;

require __DIR__ . '/bootstrap.php';
$connection = ConnectionManager::getConnection();
$connection->beginTransaction();

////////////////////////////////////////////////////////////////////////////////

use ngyuki\DoctrineTableGateway\TableGateway;

// Connection のインスタンスとテーブル名をコンストラクタに指定してインスタンスを生成する
$t = new TableGateway($connection, 'example');

// find($id) で主キーで 1 件の行を array で返す
$t->find(1);

// find() で引数を省略するとテーブルの先頭行が返る
// 後述の orderBy() で並び順を指定しなければ結果は不定かも知れない
$t->find();

// find() は行が見つからない場合は null を返す
$t->find(9999);

// all() ですべての行のイテレーターが返る
$t->all();

////////////////////////////////////////////////////////////////////////////////

// `scope()` でテーブルにスコープを適用する
$t->scope('a = 1')->all();
// => WHERE (a = 1)

// スコープは連想配列でも指定できる
$t->scope(['a' => 1])->all();
// => WHERE (a = 1)

// スコープの整数キーの値は SQL の式としてそのまま使用される
$t->scope(['a = 1', 'b = 2', 'c' => 3])->all();
// => WHERE (a = 1) AND (b = 2) AND (c = 3)

// スコープはチェインできる
$t->scope(['a' => 1])->scope(['b' => 0])->all();
// => WHERE (a = 1) AND (b = 2)

// チェインや配列で複数指定されたスコープは AND で連結される
$t->scope('a = 1 OR b = 2')->scope(['c = 3', 'd' => 4])->all();
// => WHERE (a = 1 OR b = 2) AND (c = 3) AND (d = 4)

// スコープを適用したオブジェクトは使いまわすことができる
$a = $t->scope('a = 1');
$b = $a->scope('b = 2');
$c = $a->scope('c = 3');

$a->all(); // => WHERE (a = 1)
$b->all(); // => WHERE (a = 1) AND (b = 2)
$c->all(); // => WHERE (a = 1) AND (c = 3)

// orderBy() で並び順を指定する
$t->scope('a = 1')->orderBy('b', 'DESC')->all();
// => WHERE (a = 1) ORDER BY b DESC

////////////////////////////////////////////////////////////////////////////////

$t->scope(function (\Doctrine\DBAL\Query\QueryBuilder $q) {
    return $q->where('a = 1')->andWhere('b = 2')->orderBy('c', 'desc');
}); // => WHERE (a = 1) AND (b = 1) ORDER BY c DESC

////////////////////////////////////////////////////////////////////////////////

// 指定された列値のみのイテレーターを返す
$t->all()->asColumn('col');

// 指定された列値がキー値となるイテレーターを返す
$t->all()->asUnique('col');

// 指定された 2 つの列値がキーと値となるイテレーターを返す
$t->all()->asPair('key', 'val');

// イテレーターを配列化する
$t->all()->toArray();

///

$t->all()->asColumn('id')->toArray();
//=> [1, 2, 3]

$t->all()->asUnique('id')->toArray();
//=> [
//=>   1 => ['id' => 1, 'name' => 'aaa'],
//=>   2 => ['id' => 2, 'name' => 'bbb'],
//=>   3 => ['id' => 3, 'name' => 'ccc'],
//=> ]

$t->all()->asPair('id', 'name');
//=> [
//=>   1 => 'aaa',
//=>   2 => 'bbb',
//=>   3 => 'ccc',
//=> ]

////////////////////////////////////////////////////////////////////////////////

$t->insert(['a' => 1, 'b' => 2, 'c' => 3]);
#=> INSERT INTO ... (a, b, c) VALUES (1, 2, 3)

$t->insert(['a' => 1, 'xxx' => 2]);
#=> INSERT INTO ... (a) VALUES (1)

$t->scope('a = 1', ['a' => 1])->insert(['b' => 2, 'c' => 3]);
#=> INSERT INTO ... (a, b, c) VALUES (1, 2, 3)

$t->scope('a = 1', ['b' => 2])->insert(['c' => 3]);
#=> INSERT INTO ... (b, c) VALUES (2, 3)

////////////////////////////////////////////////////////////////////////////////

$t->update(1, ['a' => 1, 'b' => 2, 'c' => 3]);
#=> UPDATE ... SET a = 1, b = 2, c = 3 WHERE id = 1

$t->delete(1);
#=> DELETE FROM ... WHERE id = 1

$t->update(1, ['a' => 1, 'xxx' => 2]);
#=> UPDATE ... SET a = 1 WHERE id = 1

$t->scope('a = 1')->update(null, ['b' => 2, 'c' => 3]);
#=> UPDATE ... SET b = 2, c = 3 WHERE a = 1

$t->scope('a = 1')->delete();
#=> DELETE FROM ... WHERE a = 1

$t->update(null, ['b' => 2, 'c' => 3]);
#=> UPDATE ... SET b = 2, c = 3

$t->delete();
#=> DELETE FROM ...

////////////////////////////////////////////////////////////////////////////////

//use ngyuki\DoctrineTableGateway\TableGateway;
use ngyuki\DoctrineTableGateway\Metadata;
use Doctrine\Common\Cache\ApcuCache;
use Cache\Adapter\Doctrine\DoctrineCachePool;

$cache = new DoctrineCachePool(new ApcuCache());
$t = new TableGateway($connection, 't_user', new Metadata($connection, $cache));
