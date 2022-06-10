<?php

namespace Vaedly\HdgGeneralPackage\Repository;

use Illuminate\Support\Facades\DB;

class Repository
{
    public $orderBy = [];
    public $limit = 0;
    public $group = [];
    public $offset = 10;
    public $page = 1;
    public $in = [];
    public $notIn = [];
    public  $between = [];

    public function getOne($table, $where = [], $field = ['*'])
    {
        return DB::table($table)
            ->where($where)
            ->when(count($this->orderBy), function ($query) {
                foreach ($this->orderBy as $key => $item) {
                    $query->orderBy($key, $item);
                }
            })
            ->first($field);
    }

    public function getFirstData($table, $where = [], $field = ['*'])
    {
        return DB::table($table)
            ->where($where)
            ->when(count($this->orderBy), function ($query) {
                foreach ($this->orderBy as $key => $item) {
                    $query->orderBy($key, $item);
                }
            })
            ->when(count($this->in), function ($query) {
                foreach ($this->in as $key => $item) {
                    $query->whereIn($key, $item);
                }
            })
            ->when(count($this->notIn), function ($query) {
                foreach ($this->notIn as $key => $item) {
                    $query->whereNotIn($key, $item);
                }
            })
            ->first($field);
    }

    public function getOneLockForUpdate($table, $where = [], $field = ['*'])
    {
        return DB::table($table)
            ->where($where)
            ->when(count($this->orderBy), function ($query) {
                foreach ($this->orderBy as $key => $item) {
                    $query->orderBy($key, $item);
                }
            })
            ->lockForUpdate()
            ->first($field);
    }


    public function checkExists($table, $where = [])
    {
        return DB::table($table)->where($where)->exists();
    }

    public function count($table, $where = [], $field = ['*'])
    {
        return DB::table($table)
            ->where($where)
            ->where(function ($query) {
                if (!empty($this->in)) {
                    foreach ($this->in as $index => $value) {
                        $query->whereIn($index, $value);
                    }
                }
            })
            ->count($field);
    }

    public function update($table, $where = [], $data)
    {
        return DB::table($table)
            ->where($where)
            ->where(function ($query) {
                if (!empty($this->in)) {
                    foreach ($this->in as $index => $value) {
                        $query->whereIn($index, $value);
                    }
                }
            })
            ->when(count($this->orderBy), function ($query) {
                foreach ($this->orderBy as $key => $item) {
                    $query->orderBy($key, $item);
                }
            })
            ->update($data);
    }

    public function updateOrInsert($table,$where = [],$data)
    {
        return DB::table($table)
            ->updateOrInsert($data,$where);
    }

    public function insert($table, $data)
    {
        return DB::table($table)->insertGetId($data);
    }

    public function insertBatch($table, $data)
    {
        return DB::table($table)->insert($data);
    }

    public function insertOne($table, $data)
    {
        return DB::table($table)->insert($data);
    }
    /**
     * 有则更新,没有则新增
     * @return int
     */
    public function firstOrCrate($table, $where = [], $data)
    {
        $exists = $this->checkExists($table, $where);
        if ($exists) {
            return $this->update($table, $where, $data);
        } else {
            return $this->insert($table, $data);
        }
    }

    public function getList($table, $where = [], $field = ['*'])
    {
        return DB::table($table)
            ->where($where)
            ->when(count($this->orderBy), function ($query) {
                foreach ($this->orderBy as $key => $item) {
                    $query->orderBy($key, $item);
                }
            })
            ->where(function ($query) {
                if (!empty($this->in)) {
                    foreach ($this->in as $index => $value) {
                        $query->whereIn($index, $value);
                    }
                }
            })
            ->where(function ($query) {
                if (!empty($this->notIn)) {
                    foreach ($this->notIn as $index => $value) {
                        $query->whereNotIn($index, $value);
                    }
                }
            })
            ->when($this->offset && $this->limit, function ($query) {
                $query->offset($this->offset);
            })
            ->when(count($this->group), function ($query) {
                $query->groupBy($this->group);
            })
            ->when($this->limit, function ($query) {
                $query->limit($this->limit);
            })
            ->when($this->between, function ($query) {
                foreach ($this->between as $index => $value) {
                    $query->wherebetWeen($index, $value);
                }
            })
            ->get($field)
            ->toArray();
    }
    /*
     * 2021-7-16  一个方法里面 不能用 2个以上 paginate  第二个的分页 offset 只会受第一个 offset影响
     *   如有DISTINCT 类似 也不能用此方法  数据统计量是去重前
     */
    public function getEachPageList($table, $where = [], $field = ['*'])
    {
        return DB::table($table)
            ->select($field)
            ->where($where)
            ->when(count($this->orderBy), function ($query) {
                foreach ($this->orderBy as $key => $item) {
                    $query->orderBy($key, $item);
                }
            })
            ->where(function ($query) {
                if (!empty($this->in)) {
                    foreach ($this->in as $index => $value) {
                        $query->whereIn($index, $value);
                    }
                }
            })
            ->when(count($this->group), function ($query) {
                $query->groupBy($this->group);
            })
            ->when($this->limit, function ($query) {
                $query->limit($this->limit);
            })
            ->forPage($this->page)
            ->paginate($this->offset);
    }

    /**
     * 自增
     */
    public function increment($table, $where, $field, $value = 1)
    {

        return DB::table($table)
            ->where($where)
            ->increment($field, $value);
    }

    public function formatPage($table, $where = [], $field = ['*'])
    {
        $builder = $this->getEachPageList($table, $where, $field);

        return [
            'total' => $builder->total(),
            'items' => $builder->items()
        ];
    }

    public function delete($table, $where)
    {
        return DB::table($table)
            ->where($where)
            ->where(function ($query) {
                if (!empty($this->in)) {
                    foreach ($this->in as $index => $value) {
                        $query->whereIn($index, $value);
                    }
                }
            })
            ->delete();
    }

    public function sql($sql)
    {
        return DB::select($sql);
    }

    /**
     * 获取一条数据并以数组形式返回
     * @param $table
     * @param $primary_value
     * @return array|\Illuminate\Database\Query\Builder|mixed
     */
    public function find($table, $primary_value, $field = ['*'])
    {
        if(!$table || !$primary_value){
            return [];
        }
        $data = DB::table($table)->select($field)->find($primary_value);
        if(!$data){
            return [];
        }
        return get_object_vars($data);
    }

    public function sum($table,$where,$field)
    {
        return DB::table($table)
            ->where($where)
            ->when($this->between, function ($query) {
                foreach ($this->between as $index => $value) {
                    $query->wherebetWeen($index, $value);
                }
            })
            ->sum($field);
    }

    /**
     * 批量更新操作
     * @param array $multipleData
     * @param array $where
     * @return false
     */
    public function updateBatch(string $table, array $multipleData, array $where = [])
    {
        try {
            if (!$table || empty($multipleData)) {
                throw new \Exception('updateBatch arg table or multipleData  empty');
            }
            $firstRow = current($multipleData);
            $updateColumn = array_keys($firstRow);
            $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
            unset($updateColumn[0]);
            $updateSql = "UPDATE " . $table . " SET ";
            $sets = [];
            $bindings = [];
            foreach ($updateColumn as $uColumn) {
                $setSql = "`" . $uColumn . "` = CASE ";
                foreach ($multipleData as $data) {
                    $setSql .= "WHEN `" . $referenceColumn . "` = ? THEN ? ";
                    $bindings[] = $data[$referenceColumn];
                    $bindings[] = $data[$uColumn];
                }
                $setSql .= "ELSE `" . $uColumn . "` END ";
                $sets[] = $setSql;
            }
            $updateSql .= implode(', ', $sets);
            $whereIn = collect($multipleData)->pluck($referenceColumn)->values()->all();
            $bindings = array_merge($bindings, $whereIn);
            $whereIn = rtrim(str_repeat('?,', count($whereIn)), ',');
            $updateSql = rtrim($updateSql, ", ") . " WHERE `" . $referenceColumn . "` IN (" . $whereIn . ")";
            if (!empty($where)) {
                $whereSql = '';
                foreach ($where as $key => $value) {
                    $whereSql .= "`{$key}` = {$value}";
                }
                $updateSql .= " AND " . $whereSql;
            }
            DB::update($updateSql, $bindings);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function pluck($table, $where, $field)
    {
        return DB::table($table)
            ->where($where)
            ->pluck($field)->toArray();
    }

    public function getValue($table, $where, $field)
    {
        return DB::table($table)
            ->where($where)
            ->orderBy('id', 'desc')
            ->value($field);
    }

    public function getPluck($table, $where, $field, $key)
    {
        return DB::table($table)
            ->whereIn($key, $where)
            ->pluck($field, $key)->toArray();
    }
}
