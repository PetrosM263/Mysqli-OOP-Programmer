<?php

namespace Controller\Mysqli;

use \Controller\Mysqli\Database;

class Search extends Database
{
    // class properties
    protected $sort_dir = "DESC"; // sort direction
    protected $keywords = null; //search keywords
    protected $tb_columns = []; // table columns
    protected $tables = []; // searching table
    protected $search = null; //search word statement 
    protected $limit = 0; // search dept limit
    protected $sort = null; // search sort
    protected $offset = 0; // search by index

    // an added sql statement for strict conditions
    protected $strict_sql = "";

    // Ranges properties
    protected $range_allowed = false; // allow searching range
    protected $RangeStart; // start range
    protected $RangeEnd; // end range

    public function __construct()
    {
        parent::__construct();

        // default sort
        $this->set_sort("published");
    }

    public function set_range($RangeStart, $RangeEnd)
    {
        $this->RangeStart = $RangeStart;
        $this->RangeEnd = $RangeEnd;

        return ($this);
    }

    public function AllowRange($option = true)
    {
        $this->range_allowed = $option;

        return ($this);
    }

    public function setPageOffset($offset = 0)
    {
        $this->offset = $offset;

        return ($this);
    }

    public function setTableColumn($name)
    {
        $table_column = trim($name); // table column name

        // add new data
        array_push($this->tb_columns, $table_column);

        return ($this);
    }

    public function setTableColumns($columns = [])
    {
        if (is_array($columns)) {
            // add new data
            $this->tb_columns = array_merge($this->tb_columns, $columns);
        }

        return ($this);
    }

    public function setTableSearch($search)
    {
        $this->keywords = $this->genkeywords($search);

        return ($this);
    }

    public function setTable($table)
    {
        $table = trim($table);
        // add new data
        array_push($this->tables, $table);

        return ($this);
    }

    public function setTables($database_tables = [])
    {
        if (is_array($database_tables)) {
            $this->tables = array_merge($this->tables, $database_tables);
        }

        return ($this);
    }

    public function
    sort_direction($direction = "DESC" || "ASC")
    {
        $this->sort_dir = $direction;

        return ($this);
    }

    public function set_sort($sort)
    {
        $this->sort = $sort;

        return ($this);
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;

        return ($this);
    }

    public function setCond($condition)
    {
        // append and create strict sql conditions
        if (strlen($this->strict_sql) > 0) {
            $this->strict_sql .= "||" . trim($condition);
        } else {
            $this->strict_sql .= trim($condition);
        }

        return ($this);
    }

    public function execute($depth = 0)
    {

        // table columns e.g id, title, description
        $columns = $this->tb_columns;

        // tables data e.g table posts
        $tables = $this->tables;

        // mysqli statement
        $statement = "";

        // mysqli database statement
        $sql = "";

        // generate keywords using dept
        switch ($depth) {
            case 0: {
                    $keywords = $this->keywords;
                    break;
                }
            case 1: {
                    $keywords = $this->refix_keywords(1);
                    break;
                }
            case 2: {
                    $keywords = $this->refix_keywords(2);
                    break;
                }
        }

        // combine search for all search_table_columns
        if (count($columns) > 0) {
            for ($i = 0; $i < count($columns); $i++) {
                foreach ($keywords as $word) {
                    $statement .= $columns[$i] . " LIKE '%$word%' OR ";
                }
            }

            // remove the last OR from the sql statement
            $statement = substr(trim($statement), 0, strlen($statement) - 3);
        }

        foreach ($tables as $table) {

            // create sql statement
            if (empty($sql)) {
                $sql = "SELECT * FROM $table WHERE ($statement)";
            } else {
                $sql .= "UNION SELECT * FROM $table WHERE ($statement)";
            }

            // append strict condition
            if (!empty($this->strict_sql)) {
                $sql .= "&&(" . $this->strict_sql . ")";
            }

            // use time range on search
            if ($this->range_allowed) {

                // if range variables are set
                if (!empty($this->RangeStart) && !empty($this->RangeEnd)) {
                    $sql .= " && published BETWEEN $this->RangeStart AND $this->RangeEnd";
                }
            }
        }

        // if sort is specified
        if (!empty($this->sort)) {
            $sql .= " ORDER BY $this->sort " . $this->sort_dir;
        }

        // search using offset variable
        if (!empty($this->offset - 1) && !empty($this->limit)) {
            $start_from = $this->offset * $this->limit;
            $sql .= " LIMIT " . $start_from . ", " . $this->limit;
        }

        // set sql result limit
        else if ($this->limit > 0) {
            $sql .= " LIMIT " . $this->limit;
        }

        $result = $this->execute_SQL($sql);



        // if matching results was found
        if (!empty($result->num_rows)) {
            return ($result);
        } else {

            //if no matching results was found change depth
            for ($i = $depth + 1; $i < 2; $i++) {
                $result = $this->execute($i);

                if (!empty($result->num_rows)) {
                    return ($result);
                    break;
                }
            }

            // no matching result was found
            return ("Nothing was found for sql: " . $sql);
        }
    }

    protected function genkeywords($search)
    {
        //prepair keywords
        $IgnoreKeys = $this->ignore_keywords();

        // split keywords
        if (str_contains($search, "_")) {
            $keywords = explode("_", $search);
        } else {
            $keywords = explode(" ", $search);
        }

        $filtered  = array();
        $ignore = false;

        foreach ($keywords as $key) {
            foreach ($IgnoreKeys as $Ignore) {
                if (strtolower(trim($Ignore)) == strtolower(trim($key))) {
                    $ignore = true;
                }
            }

            if ($ignore == false) {
                array_push($filtered, $key);
            } else {
                $ignore = false;
            }
        }
        return $filtered;
    }

    protected function refix_keywords($depth = 1)
    {
        //load search key words
        $keywords = $this->keywords;

        switch ($depth) {
            case 1: {
                    $refixed_words = array();
                    $refixed = false;

                    if (!empty($keywords)) {
                        foreach ($keywords as $words) {
                            $lenght = strlen($words);
                            $chars = str_split($words);
                            $new_word = "";
                            $index = 0;
                            $count = 0;

                            while ($index <= $lenght - 1) {
                                if ($count <= count($chars) - 1) {
                                    if (!$refixed && $index == $count) {
                                        $new_word .= '_';
                                        $refixed = true;
                                    } else $new_word .= $chars[$count];
                                    $count++;
                                }
                                if ($count == count($chars)) {
                                    // push new word to the refix words to encounter spelling errors 
                                    array_push($refixed_words, $new_word);
                                    $refixed = false;
                                    $new_word = "";
                                    $count = 0;
                                    $index++;
                                }
                            }
                        }
                    }

                    if (!empty($refixed_words))
                        return $refixed_words;
                    else
                        return null;
                };
            case 2: {
                    $refixed_words = array();
                    $refixed = false;

                    if (!empty($keywords)) {
                        foreach ($keywords as $words) {
                            $lenght = strlen($words);
                            $chars = str_split($words);
                            $new_word = "";
                            $index = 0;
                            $count = 0;

                            while ($index <= $lenght - 1) {
                                if ($count <= count($chars) - 1) {
                                    if (!$refixed && $index == $count) {
                                        $new_word .= '%';
                                        $refixed = true;
                                    } else $new_word .= $chars[$count];
                                    $count++;
                                }
                                if ($count == count($chars)) {
                                    // push new word to the refix words to encounter spelling errors 
                                    array_push($refixed_words, $new_word);
                                    $refixed = false;
                                    $new_word = "";
                                    $count = 0;
                                    $index++;
                                }
                            }
                        }
                    }

                    if (!empty($refixed_words))
                        return $refixed_words;
                    else
                        return null;
                };
        }
    }

    protected function ignore_keywords()
    {
        return (array(
            "about",
            "above",
            "actually",
            "after",
            "again",
            "against",
            "all",
            "almost",
            "also",
            "although",
            "always",
            "am",
            "an",
            "and",
            "any",
            "are",
            "as",
            "at",
            "be",
            "became",
            "become",
            "because",
            "been",
            "before",
            "being",
            "below",
            "between",
            "both",
            "but",
            "by",
            "can",
            "could",
            "did",
            "do",
            "does",
            "doing",
            "down",
            "during",
            "each",
            "either",
            "else",
            "few",
            "for",
            "from",
            "further",
            "had",
            "has",
            "have",
            "having",
            "he",
            "he'd",
            "he'll",
            "hence",
            "he's",
            "her",
            "here",
            "here's",
            "hers",
            "herself",
            "him",
            "himself",
            "his",
            "how",
            "how's",
            "I",
            "I'd",
            "I'll",
            "I'm",
            "I've",
            "if",
            "in",
            "into",
            "is",
            "it",
            "it's",
            "its",
            "itself",
            "just",
            "let's",
            "may",
            "maybe",
            "me",
            "might",
            "mine",
            "more",
            "most",
            "must",
            "my",
            "myself",
            "neither",
            "nor",
            "not",
            "of",
            "oh",
            "on",
            "once",
            "only",
            "ok",
            "or",
            "other",
            "ought",
            "our",
            "ours",
            "ourselves",
            "out",
            "over",
            "own",
            "same",
            "she",
            "she'd",
            "she'll",
            "she's",
            "should",
            "so",
            "some",
            "such",
            "than",
            "that",
            "that's",
            "the",
            "their",
            "theirs",
            "them",
            "themselves",
            "then",
            "there",
            "there's",
            "these",
            "they",
            "they'd",
            "they'll",
            "they're",
            "they've",
            "this",
            "those",
            "through",
            "to",
            "too",
            "under",
            "until",
            "up",
            "very",
            "was",
            "we",
            "we'd",
            "we'll",
            "we're",
            "we've",
            "were",
            "what",
            "what's",
            "when",
            "whenever",
            "when's",
            "where",
            "whereas",
            "wherever",
            "where's",
            "whether",
            "which",
            "while",
            "who",
            "whoever",
            "who's",
            "whose",
            "whom",
            "why",
            "why's",
            "will",
            "with",
            "within",
            "would",
            "yes",
            "yet",
            "you",
            "you'd",
            "you'll",
            "you're",
            "you've",
            "your",
            "yours",
            "yourself",
            "yourselves",
            "news",
            "latest",
            "article",
            "blog",
            "post"
        ));
    }
}
