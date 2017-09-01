<?php
namespace ngyuki\DoctrineTableGateway;

class ResultSet extends ResultIterator
{
    /**
     * @param string $column
     *
     * @return ResultIterator
     */
    public function asColumn($column)
    {
        return new ResultIterator((function () use ($column) {
            foreach ($this as $key => $arr) {
                yield $key => $arr[$column];
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
     * @param string $key
     * @param string $val
     *
     * @return ResultIterator
     */
    public function asPair($key, $val)
    {
        return $this->asUnique($key)->asColumn($val);
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
        $map = null;
        foreach ($this as $row) {

            if ($map === null) {
                $map = [];
                foreach ($row as $k => $_) {
                    $map[] = $k;
                }
            }

            unset($ref);
            $ref =& $ret;
            foreach ((array)$key as $k) {
                if (is_int($k)) {
                    $k = $map[$k];
                }
                $ref =& $ref[$row[$k]];
            }

            if ($column === null) {
                $ref = $row;
            } else {
                if (is_array($column)) {
                    $ref = [];
                }
                foreach ((array)$column as $col) {
                    if (is_int($col)) {
                        $col = $map[$col];
                    }
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
