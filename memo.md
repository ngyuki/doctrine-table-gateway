
## プロトタイプパターン vs ファクトリパターン

プロトタイプパターンとファクトリパターンだとどっちが早いか？

→
ArrayObject で試したところ プロトタイプパターン の方が早い。
構築について複雑な処理が不要ならプロトタイプパターンのほうが良さそう。

## 名前と順序のパラメータ

eq とかでパラメータを指定するとき、ユーザーコードでどっちが使われているかわからないと名前にするか順序にするか判断できない。

- 常に quote で埋め込む？
- 雑に自動判定する？
- どっちかしか使えないことにする？

doctrine-dbal はライブラリ側でその手のバインドは行われず、アプリでバインドするかクオートするかぐらい。

ZF はクオートして埋め込まれる？ コードを見た感じ名前付きになることもある？ 順序になることはない？

## into とか

TableGateway にショートハンド用のメソッドを生やす。

```php
$t->scope([
    'name = ?' => $t->into($name),
    'name like ?' => $t->like($name),       // name like %{$name}%
    'name like ?' => $t->likeLeft($name),   // name like %{$name}
    'name like ?' => $t->likeRight($name),  // name like {$name}%
]);
```

## join とか

```php
// [tags => $tags]
$t->alias('A')->hasMany('tags', 't_tag', 'T', ['T.tag_id' => 'A.tag_id']);
// [tag => $tag]
$t->alias('A')->hasOne('tag', 't_tag', 'T', ['T.tag_id' => 'A.tag_id']);
```

join とか必要なら SQL を直で書けば良い気がする。

## query

SQL を直で書きたいときに使うメソッド。

スコープとして扱うか ResultSet を返すか。

```php
// スコープ
$t->query($sql)->all()->current();

// ResultSet を返す
$t->query($sql)->current();
```

スコープとして扱ってもその次にできることは `all()` しか無いので直接 ResultSet を返すことにする。

## asUnique

asUnique で引数の配列を指定することで多次元配列を返せるようにしようと思ったけど・・イテレーターだと簡単ではない。。

そもそも型指定が死んでしまうし、多次元配列を返す別のバージョンを作るか。

```php
asGroup($keys, $vals)
```

## all の戻り値

all がイテレーターを返すようにしたのは、型付きで実装するときにメソッドの型宣言でイテレータークラスを指定できるようにするため。

がしかし、Phan とかで静的解析する前提なら `@return Uset[]` とかで良い気もする。。。

あと、イテレーターでメソッドチェインすることとか考えたけど。。

```php
// id => name のペアを返す
$t->all()->asUnique('id')->asColumn('name');
```

TableGateway に fetchXXX なメソッドが生えているだけで十分な気もする。

```php
$t->fetch();
$t->fetchAll();
$t->fetchUnique($key);
$t->fetchPair($key, $val);
$t->fetchOne($cols);
$t->fetchColumn($cols);
```

イテレーターでの実装のほうが数は少なくてすむ。

```php
$t->all()->current();
$t->all()->toArray();
$t->all()->asUnique($key)->toArray();
$t->all()->asUnique($key)->asColumns($val)->toArray();
$t->all()->asColumns($val)->current();
$t->all()->asColumns($val)->toArray();
```

イテレーターを返す前提にすると、型付きにするときに UserResultSet みたいなものまで実装する必要があって煩雑。

...

テーブルゲートウェイにスコープの概念を導入したわけなので TableGateway=ResultSet みたいな感じにしても良いかも？

つまり TableGateway をイテレーターにして rewind でクエリを実行する。

- IteratorAggregate を実装する
- getIterator でクローンした TableGateway を返す
- TableGateway 自身が ResultSet を保持する

IteratorAggregate は Iterator を返さなければならないので↑の実装は不可能。
やるなら TableGateway は Iterator でなければならない。

Iterator を実装するようにすると同じ TableGateway のインスタンスを foreach で入れ子するとおかしなことになる。。。
Iterator は状態を持つわけなのでそれを TableGateway が実装することには違和感がある。
やっぱり ResultSet を実装する必要あるか。。。
