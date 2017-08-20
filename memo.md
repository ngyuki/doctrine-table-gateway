
## プロトタイプパターン vs ファクトリパターン

プロトタイプパターンとファクトリパターンだとどっちが早いか？

→
ArrayObject で試したところ プロトタイプパターン の方が早い。
構築について複雑な処理が不要ならプロトタイプパターンのほうが良さそう。

## スコープ

検索条件はすべてスコープで表現すると良いかも

```php
$userTable->scope(['name' => 'ore'])->delete();
$userTable->scope(['email' => 'ore@example.com'])->update(['name' => 'are'])
$userTable->scopeActive()->all();
$userTable->scopeAll()->find(1);

class UserTable
{
    function __construct()
    {
        $this->addScope(
            // select とかの条件
            'disable_flg = 1',
            // insert のデフォルト値
            ['disable_flg' => 0]
        );
    }

    function scopeAdmin()
    {
        return $this->scope(
            'admin_flg = 1',
            // insert のデフォルト値
            ['admin_flg' => 0]
        );
    }

    function scopeAll()
    {
        return $this->resetScopez();
    }
}
```

## 名前と順序のパラメータ

eq とかでパラメータを指定するとき、ユーザーコードでどっちが使われているかわからないと名前にするか順序にするか判断できない。

- 常に quote で埋め込む？
- 雑に自動判定する？
- どっちかしか使えないことにする？

doctrine-dbal はライブラリ側でその手のバインドは行われず、アプリでバインドするかクオートするかぐらい。

ZF はクオートして埋め込まれる？ コードを見た感じ名前付きになることもある？ 順序になることはない？

## insert のデフォルト

scope で単純な連想配列を指定したときは insert のデフォルト値としても使えることにする。
クロージャーとかで自動判定できないときは scope の第２引数で指定するとか。

```php
$t->scope(function ($q) { $q->where('disable = 1'); }, ['disabled' => 1]);
```

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






















