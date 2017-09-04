<?php
namespace ngyuki\DoctrineTableGateway;

class ResultSet extends ResultIterator
{
    /**
     * 指定した列値だけを列挙するイテレーターを返します
     *
     * $column に文字列を指定すると列名を指定できます
     * $column に配列を指定すると結果も配列を列挙するイテレーターになります
     *
     * {code}
     *      $t->all->asColumn('name');          // ['foo', 'bar']
     *      $t->all->asColumn(['id', 'name']);  // [[1, 'foo'], [2, 'bar']]
     * {/code}
     *
     * @param string|string[] $column
     *
     * @return ResultIterator
     */
    public function asColumn($column)
    {
        return new ResultIterator((function () use ($column) {
            $map = null;
            foreach ($this as $key => $row) {
                $val = [];
                foreach ((array)$column as $col) {
                    if (is_array($column)) {
                        $val[] = $row[$col];
                    } else {
                        $val = $row[$col];
                    }
                }
                yield $key => $val;
            }
        })());
    }

    /**
     * @param string $column
     *
     * @return static
     */
    public function asUnique($column)
    {
        return new static((function () use ($column) {
            $prev = null;
            foreach ($this as $arr) {
                $key = $arr[$column];
                if ($prev !== $key) {
                    $prev = $key;
                    yield $key => $arr;
                }
            }
        })());
    }

    /**
     * @param string           $key
     * @param int|string|array $column
     *
     * @return ResultIterator
     */
    public function asPair($key, $column)
    {
        return $this->asUnique($key)->asColumn($column);
    }

    /**
     * 指定された列のキーでグループ化し、指定された列の値のみで配列化して返す
     *
     * $key で指定した列の値が結果の連想配列のキーになります
     * $key を配列で指定すると、多次元の連想配列として結果が返ります
     *
     * $val で指定した列の値が連想配列の値になります
     * $val を配列で指定すると、列の値が指定した順番通りで配列になります
     * $val に null を指定すると、連想配列の値は元の列の値そのままになります
     *
     * @param string|array      $key
     * @param string|array|null $column
     *
     * @return array
     */
    public function asGroup($key, $column = null)
    {
        $ret = [];
        $ref = null;
        foreach ($this as $row) {
            unset($ref);
            $ref =& $ret;
            foreach ((array)$key as $k) {
                $ref =& $ref[$row[$k]];
            }

            if ($column === null) {
                $ref = $row;
            } else {
                if (is_array($column)) {
                    $ref = [];
                }
                foreach ((array)$column as $col) {
                    if (is_array($column)) {
                        $ref[] = $row[$col];
                    } else {
                        $ref = $row[$col];
                    }
                }
            }
        }
        return $ret;
    }
}
