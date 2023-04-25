<?php

namespace Controller\Mysqli;

use \Controller\Mysqli\Database;

class Counters extends Database
{
    function newView($table, $article_id)
    {
        $article = $this->execute_SQL("SELECT * FROM $table WHERE id='$article_id'");
        if (isset($article->num_rows)) {
            if ($article->num_rows > 0) {
                $article = $article->fetch_assoc();
                $views = $article['views'] + 1;
                $sql = "UPDATE $table SET views='$views' WHERE id='$article_id'";
                if ($this->execute_SQL($sql)) {
                    return $views;
                }
            }
        }
    }

    function viewsCounter($table, $article_id)
    {
        $sql = "SELECT * FROM $table WHERE id='$article_id'";
        $result = $this->execute_SQL($sql);

        if (isset($result->num_rows)) {
            if ($result->num_rows > 0) {
                $article  = $result->fetch_assoc();
                $views = $article['views'];
                return $views;
            }
        }
    }

    function count($table, $category)
    {
        $sql = "SELECT * FROM $table where category='$category' && is_active=1";
        $result = $this->execute_SQL($sql);
        $total = 0;

        if (!empty($result->num_rows) && $result->num_rows > 0) {
            $total = $result->num_rows - 1;
        }

        // total
        return ($total);
    }
}
