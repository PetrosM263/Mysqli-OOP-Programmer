<?php

namespace Controller\Mysqli;

use \Controller\Mysqli\Database;
use \Controller\Mysqli\Search;

class Pagination extends Database
{
    protected $num_per_page;      // pagination numbers per page
    protected $status;            // sqli post status 0= not active | 1= active
    protected $search;            // search words
    protected $tables = [];    // database tables
    protected $search_columns = [];   // database table columns
    protected $url = "?page={?}&&cat={?}::$1=page$2=category"; // pagination uri base with optional params
    protected $condition = false; // sqli condition

    public function setTable($table)
    {
        if (!in_array($table, $this->tables)) {
            array_push($this->tables, $table);
        }

        return ($this);
    }

    public function setTables($database_tables = [])
    {
        if (is_array($database_tables)) {
            $this->tables = array_merge($this->tables, $database_tables);
        }

        return ($this);
    }

    public function setNumPerPage($num)
    {
        $this->num_per_page = trim($num);

        return ($this);
    }

    public function setActiveStatus($status)
    {
        $this->status = trim($status);

        return ($this);
    }

    public function setPaginationUri($url_structure = "?page={?}&&cat={?}$1=page2=category")
    {
        $this->url = trim($url_structure);

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

    public function getPaginationUri($params = [])
    {
        // url parameters ----------------------
        $var_define = explode("::", trim($this->url));
        $url_params = explode("{?}", $var_define[0]);
        $var_set = end($var_define);
        $uri = "";

        // check for set url queries
        $uri_queries = [];

        // extract url queries to array
        parse_str($_SERVER['QUERY_STRING'], $uri_queries);

        // check if variable sets were defined or else abort 
        if (substr($var_set, 0, 1) === "$") {
            $var_array = explode("$", $var_set);

            // loop through url parameters 
            foreach ($url_params as $index => $param) {

                // if parameter is not null or empty
                if (!empty($param)) {

                    // if last letter of parameter has equal of undifined =?,the value should be dynamically set
                    if (substr($param, -1, 1) === "=") {

                        // loop through variable array
                        foreach ($var_array as $variable) {

                            // check if variable if not not empty
                            if (!empty($variable)) {

                                // explode variable data
                                $i = explode("=", $variable);

                                // get variable index number
                                $num = $i[0];

                                // get variable index value
                                $value = end($i);

                                // if variable index equals [url params] index +1
                                if ($num == ($index + 1)) {

                                    // check if variable index value was defined in parameters data
                                    if (isset($params[$value])) {

                                        // remove ? mark if included
                                        $param = (str_contains($param, "?")) ? substr($param, 1) : $param;

                                        // remove equals from parameter
                                        $param = substr($param, 0, -1);

                                        // loop through all url queries parameter and values
                                        foreach ($uri_queries as $parameter => $val) {

                                            // if url query parameter is equals to the param value 
                                            if ($parameter == $param) {

                                                // delete parameter if it was already defined
                                                unset($uri_queries[$parameter]);
                                            }
                                        }

                                        // merge and create new uri queries with new parameters and values
                                        $uri_queries =  array_merge($uri_queries, [$param => $params[$value]]);

                                        // loop through all url queries parameter and values 
                                        foreach ($uri_queries as $parameter => $value) {

                                            // if parameter query is category or parameter value is empty
                                            if ($parameter == "category" || empty($value)) {

                                                // delete parameter from query
                                                unset($uri_queries[$parameter]);
                                            }
                                        }

                                        // return uri
                                        return ("?" . http_build_query($uri_queries));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getTotalUriParams()
    {
        $uri = explode("{?}", $this->url);
        $total = 0;

        foreach ($uri as $params) {

            // if parameter is not equal to null 
            if (!empty($params)) {

                // count parameter
                $total++;
            }
        }

        return ($total);
    }

    public function setCond($sqli)
    {
        $this->condition = $sqli;

        return ($this);
    }

    public function get_results($params = [])
    {
        $pagination = array();

        // check if table, num_per_pag & status is set --------
        if (!empty(count($this->tables)) && !empty($this->num_per_page) && !empty($this->status)) {

            if (empty($this->search)) {

                // using straight mysqli statement
                $sql = "";
                foreach ($this->tables as $table) {
                    if (empty($sql)) {

                        // if mysqli condition is defined
                        if (!empty($this->condition) && $this->condition != false) {
                            $sql .= "SELECT * FROM " . $table . " WHERE is_active=" . $this->status . "&&(" . $this->condition . ")";
                        }

                        // if mysqli condition is not defined
                        else {
                            $sql .= "SELECT * FROM " . $table . " WHERE is_active=" . $this->status;
                        }
                    } else {

                        // if mysqli condition is defined
                        if (!empty($this->condition) && $this->condition != false) {
                            $sql .= " UNION SELECT * FROM " . $table . " WHERE is_active=" . $this->status . "&&(" . $this->condition . ")";
                        }

                        // if mysqli condition is not defined
                        else {
                            $sql .= " UNION SELECT * FROM " . $table . " WHERE is_active=" . $this->status;
                        }
                    }
                }

                $rows = $this->execute_SQL($sql);
            } else {

                // using search adapter
                $n = new Search();
                $rows =  $n->setTables($this->tables)
                    ->setTableColumns($this->search_columns)
                    ->setTableSearch($this->search)
                    ->execute();
            }

            if (!empty($rows->num_rows)) {
                $total = $rows->num_rows;

                // calculate number of pages using numbers of results per page
                $pages = ($total >= $this->num_per_page) ? ceil($total / $this->num_per_page) : 1;

                $i = 0;
                while ($i < $pages) {
                    ++$i;
                    $params['page'] = $i;

                    $uri = $this->getPaginationUri($params);
                    array_push($pagination, ["name" => $i, "url" => $uri]);
                }

                if (!empty(count($pagination))) {
                    return (array(
                        "success" => true,
                        "data" => $pagination
                    ));
                } else

                    return (array(
                        "success" => false,
                        "data" => [],
                        "mssg" => "It seems like total results are less than numbers per page"
                    ));
            }

            // return false- no result was found
            return (array(
                "success" => false,
                "mssg" => "Error: mysqli could not find any matching results using query (" . (empty($this->search) ? $sql : null) . ")"
            ));
        } else {

            // report unset & undefined variables
            $mssg = "Note: Some important variables were not set";
            $mssg .= (empty($this->tables)) ? ", database table cannot be empty (use setTable()) to set variable" : null;
            $mssg .= (empty($this->num_per_page)) ? ", results per page cannot be empty (use setNumPerPage()) to set a variable" : null;
            $mssg .= (empty($this->status)) ? ", active status cannot be empty (use setActiveStatus()) to set a variable" : null;

            return (array(
                "success" => false,
                "mssg" => "Error: Some variables cannot be null or undefined",
                "err" => $mssg
            ));
        }
    }

    public function gen_pagination($data = [], $page = 1)
    {
        $output = "";
        $active = "";
        $count = 0;
        $last_added = [];
        $last_link = end($data);
        $first_link = $data[0];
        $page = (empty($page)) ? 1 : $page;
        $disabled = ($page <= 1) ? "disabled" : null;

        if (count($data) > 1) {
            $output .= '<nav aria-label="Page navigation">';
            $output .= '<ul class="pagination m-0 p-0">';

            // add Previous button link
            $output .= '<li class="page-item ' . $disabled . '">';
            $output .= '<a class="page-link shadow-none" href="' . $this->getPaginationUri(["page" => ($page - 1)]) . '" aria-label="Previous">';
            $output .= '<span aria-hidden="true">Previous</span>';
            $output .= '</a>';
            $output .= '</li>';

            foreach ($data as $row) {
                $active = ($page == $row['name']) ? "active" : null;
                $disabled = ($page == count($data)) ? "disabled" : null;

                // if links added are less than 3
                // Or total links minuse active page is less than 3 
                if ($count < 3 || (count($data) - $page) < 3) {
                    $output .= '<li class="page-item ' . $active . '">';
                    $output .= '<a class="page-link shadow-none mx-1" href="' . $row['url'] . '">' . $row['name'] . '</a>';
                    $output .= '</li>';

                    $last_added = $row;
                    $count++;
                }

                unset($active);
            }

            // add last link
            if ($last_added['name'] != $last_link['name']) {
                $output .= '<li class="page-item ' . $active . '">';
                $output .= '<a class="page-link shadow-none mx-1" href="' . $last_link['url'] . '">' . $last_link['name'] . '</a>';
                $output .= '</li>';
            }

            // add Next button link
            $output .= '<li class="page-item ' . $disabled . '">';
            $output .= '<a class="page-link shadow-none" href="' . $this->getPaginationUri(["page" => ($page + 1)]) . '" aria-label="Next">';
            $output .= '<span aria-hidden="true">Next</span>';
            $output .= '</a>';
            $output .= '</li>';

            $output .= '</ul>';
            $output .= '</nav>';
        }

        return ($output);
    }
}
