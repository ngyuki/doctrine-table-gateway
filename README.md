# doctrine-table-gateway

doctrine/dbal をバックエンドにしたシンプルなテーブルゲートウェイ。

## インストール

```php
composer require ngyuki/doctrine-table-gateway:dev-master
```

## 使用例

```php
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
```

## スコープ

`scope()` で `find()` や `all()` の結果の範囲を絞ることができます。

```php
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
```

スコープにはクロージャーが指定できます。クロージャーの引数の `Doctrine\DBAL\Query\QueryBuilder` を用いて自由にクエリをカスタマイズできます。

```php
$t->scope(function (\Doctrine\DBAL\Query\QueryBuilder $q) {
    return $q->where('a = 1')->andWhere('b = 2')->orderBy('c', 'desc');
}); // => WHERE (a = 1) AND (b = 1) ORDER BY c DESC
```

## イテレータ

`all()` メソッドが返すイテレータにはいくつかの便利メソッドが定義されています。

```php
// 指定された列値のみのイテレーターを返す
$t->all()->asColumn('col');

// 指定された列値がキー値となるイテレーターを返す
$t->all()->asUnique('col');

// 指定された 2 つの列値がキーと値となるイテレーターを返す
$t->all()->asPair('key', 'val');

// イテレーターを配列化する
$t->all()->toArray();
```

例えば次のようなテーブルがあるとき、

| id  | name
| --- | ---
| 1   | aaa
| 2   | bbb
| 3   | ccc

それぞれ次のように返ります。

```php
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
```

## INSERT

```php
$t->insert(['a' => 1, 'b' => 2, 'c' => 3]);
#=> INSERT INTO ... (a, b, c) VALUES (1, 2, 3)
```

INSERT で存在しない列名を指定しても無視されます。

```php
$t->insert(['a' => 1, 'xxx' => 2]);
#=> INSERT INTO ... (a) VALUES (1)
```

スコープの第２引数で INSERT のデフォルト値を指定できます。

```php
$t->scope('a = 1', ['a' => 1])->insert(['b' => 2, 'c' => 3]);
#=> INSERT INTO ... (a, b, c) VALUES (1, 2, 3)
```

スコープの第２引数が省略された場合は、第１引数の `key => value` の形式の値のみがデフォルト値として使用されます。

```php
$t->scope('a = 1', ['b' => 2])->insert(['c' => 3]);
#=> INSERT INTO ... (b, c) VALUES (2, 3)
```

## UPDATE/DELETE

`update($data)` で指定したデータで、スコープのすべての行を UPDATE します。

```php
$t->scope('a = 1')->update(['b' => 2, 'c' => 3]);
#=> UPDATE ... SET b = 2, c = 3 WHERE a = 1
```

`update($data)` で存在しない列を指定しても無視されます。

```php
$t->scope('a = 1')->update(['b' => 2, 'xxx' => 9]);
#=> UPDATE ... SET b = 2 WHERE a = 1
```

`update($data)` でスコープのすべての行を DELETE します。

```php
$t->scope('a = 1')->delete();
#=> DELETE FROM ... WHERE a = 1
```

主キーを指定したいときは `by($id)` でスコープを適用してください。

```php
$t->by(1)->update(['a' => 1, 'b' => 2, 'c' => 3]);
#=> UPDATE ... SET a = 1, b = 2, c = 3 WHERE id = 1

$t->by(1)->delete();
#=> DELETE FROM ... WHERE id = 1
```

スコープが適用されていないときはすべての行が対象になります。

```php
$t->update(['a' => 1, 'b' => 2, 'c' => 3]);
#=> UPDATE ... SET a = 1, b = 2, c = 3

$t->delete();
#=> DELETE FROM ...
```

## メタデータキャッシュ

デフォルトでは TableGateway がインスタンス化されるたびにテーブル定義のメタデータをデータベースから取得しますが、次のようにキャッシュを使うことができます。

```php
use ngyuki\DoctrineTableGateway\TableGateway;
use ngyuki\DoctrineTableGateway\Metadata;

$t = new TableGateway($connection, 't_user', new Metadata($connection, $cache));
```

`$cache` には PSR-16 の `Psr\SimpleCache\CacheInterface` をインプリメントしたオブジェクトが指定できます。

`cache/doctrine-adapter` 
doctorine のキャッシュを使う場合はは [cache/doctrine-adapter](https://packagist.org/packages/cache/doctrine-adapter) が使えます。

```sh
composer require cache/doctrine-adapter
```

```php
use ngyuki\DoctrineTableGateway\TableGateway;
use ngyuki\DoctrineTableGateway\Metadata;
use Doctrine\Common\Cache\ApcuCache;
use Cache\Adapter\Doctrine\DoctrineCachePool;

$cache = new DoctrineCachePool(new ApcuCache());
$t = new TableGateway($connection, 't_user', new Metadata($connection, $cache));
```

## テーブル結合

そういうのはできない。

OneToMany/ManyToOne/ManyToMany のような関係でどのようにテーブルを走査すればいいかは機械的に判断できるものではないと思うので。

どうしてもやりたければ `scope()` にクロージャーを渡してクエリビルダをごちゃごちゃすればできると思います。
