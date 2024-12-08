<?php

class updateSql
{
    function __construct()
    {

        if (file_exists("uploads/sql.php")) {
            include "uploads/sql.php";
            if ($sql == null or $sql == "") {
                return;
            }
            \Ofey\Logan22\model\db\sql::run($sql);
            unlink("uploads/sql.php");
        }
    }

}

