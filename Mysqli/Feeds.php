<?php

namespace Controller\Mysqli;

use \Controller\Mysqli\Database;
use \Controller\Mysqli\Search;

class Feeds extends Database
{
    protected $RangeStart;  // range datetime start
    protected $RangeEnd; // range datetime end
    protected $range_allowed = false; // is range allowed
    protected $orderBy = "id"; // order by column
    protected $sort = "DESC"; // sort column order
    protected $offset = 0; // page / offset number
    protected $limit; // sqli search limit
    protected $tables = []; // sql table search
    protected $condition = false; // sqli condition
    protected $search; // searrch query
    protected $search_columns = []; //database table search columns

    public function setRange($RangeStart, $RangeEnd)
    {
        $this->RangeStart = $RangeStart;
        $this->RangeEnd = $RangeEnd;

        return ($this);
    }

    public function AllowRange($allow = true)
    {
        $this->range_allowed = $allow;

        return ($this);
    }

    public function setSort($sort)
    {
        $this->sort = $sort;

        return ($this);
    }

    public function setOrderBy($column)
    {
        $this->orderBy = $column;

        return ($this);
    }

    public function setOffset($offset = 0)
    {
        $this->offset = $offset;

        return ($this);
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;

        return ($this);
    }

    public function setTable($table)
    {
        array_push($this->tables, $table);

        return ($this);
    }

    public function setTables($tables = [])
    {
        if (is_array($tables)) {
            $this->tables = array_merge($this->tables, $tables);
        }

        return ($this);
    }

    public function setCond($sqli)
    {
        $this->condition = $sqli;

        return ($this);
    }

    public function useSearchAdapter($search_columns = [], $search)
    {
        if (is_array($search_columns) && !empty($search)) {
            $this->search = trim($search);
            $this->search_columns = $search_columns;
        }

        return ($this);
    }

    protected function isRangeSet()
    {
        if (empty($this->RangeStart) || empty($this->RangeEnd)) {
            return (false);
        } else {
            return (true);
        }
    }

    public function execute()
    {
        if (count($this->tables) > 0) {
            if (empty($this->search) && empty(count($this->search_columns))) {

                $sql = ""; // mysqli statement

                foreach ($this->tables as $table) {
                    // sql db search statement
                    if (empty($sql)) {
                        $sql = "SELECT * FROM " . $table;
                    } else {
                        $sql .= " UNION SELECT * FROM " . $table;
                    }

                    // set mysqli condition and order by
                    if (!empty($this->condition) && $this->condition != false) {
                        $sql .= "  WHERE " . $this->condition;

                        // set time range if range is set and allowed
                        if ($this->range_allowed && $this->isRangeSet()) {
                            $sql .= " && published BETWEEN " . $this->RangeStart . " AND " . $this->RangeEnd;
                        }
                    }
                }

                // set mysqli order by and sort
                $sql .= " ORDER BY " . $this->orderBy . " " . $this->sort;

                // set search minite and pagination
                if ($this->offset > 1 && !empty($this->limit)) {

                    // set offset and limit
                    $sql .= " LIMIT " . $this->offset . ", " . $this->limit;
                } else if (!empty($this->limit)) {

                    // set limit
                    $sql .= " LIMIT " . $this->limit;
                }

                // fetch results
                $result = $this->execute_SQL($sql);
            } else {

                // else use search adapter
                $n =  new Search();
                $n->setTables($this->tables)
                    ->setTableColumns($this->search_columns)
                    ->setLimit($this->limit)
                    ->setPageOffset($this->offset)
                    ->setTableSearch($this->search);

                $res = $n->execute();

                // on success
                if ($res['success']) {
                    $result = $res['data'];
                }
            }

            if (!empty($result->num_rows)) {
                return (array(
                    "success" => true,
                    "data" => $result
                ));
            }
        } else {
            $mssg = "Mysqli database table should be strictly set (use setTable(String) or setTables(array[]))";
            return (array(
                "success" => false,
                "mssg" => $mssg,
                "err" => ""
            ));
        }
    }
}
