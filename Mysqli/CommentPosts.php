<?php

namespace Controller\Mysqli;

use \Controller\Mysqli\Database;

class CommentPosts extends Database
{
    public function writeComment($newsId, $username, $email, $comment, $is_active)
    {
        // safe input treatment
        $comment = htmlentities(trim($comment));

        // safe input treatment
        $username = htmlentities(trim(strip_tags($username)));

        // validate input safity
        $safeInput = $this->validateInputSafity($comment);

        // active status
        $is_active = ($safeInput) ? $is_active : 2;

        // published data
        $published = strtotime(date(DATE_RSS));

        $stmt = $this->conn->prepare("INSERT INTO  tb_post_comments (newsId, username, email, comment, published, is_active) VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("isssii", $newsId, $username, $email, $comment, $published, $is_active);
        if ($stmt->execute()) {

            // return success
            return (array(
                "success" => true,
                "mssg" => "New comment was posted successfully"
            ));
        } else {

            // return false
            return (array(
                "success" => false,
                "mssg" => "Error: Mysqli error encounted",
                "err" => $this->log_error()
            ));
        }
    }

    public function getComments($newsId, $limit, $active = 1)
    {
        $stmt = $this->conn->prepare("SELECT * FROM tb_post_comments WHERE newsId=? AND is_active=? LIMIT ?");
        $stmt->bind_param("iii", $newsId, $active, $limit);

        if ($stmt->execute()) {
            $rows  = $stmt->get_result();

            // return data
            return (array(
                "success" => true,
                "data" => $rows
            ));
        } else {

            // return error
            return (array(
                "success" => false,
                "mssg" => "Error: mysqli error encounted",
                "err" => $this->log_error()
            ));
        }
    }

    protected function validateInputSafity($haystack)
    {

        return (true);
    }
}
