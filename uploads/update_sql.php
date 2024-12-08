<?php

class updateSql
{
    function __construct()
    {

        if (file_exists("uploads/sql.php")) {
            foreach (include "uploads/sql.php" AS $arr){
                \Ofey\Logan22\model\db\sql::run($arr);
            }
            unlink("uploads/sql.php");
        }
    }

}

