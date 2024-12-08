<?php

class updateSql
{
    function __construct()
    {
        if (file_exists("uploads/sql.php")) {
            $data = include "uploads/sql.php";
            if(empty($data)){
                unlink("uploads/sql.php");
                return;
            }
            foreach (include "uploads/sql.php" AS $arr){
                \Ofey\Logan22\model\db\sql::run($arr);
            }
            unlink("uploads/sql.php");
        }
    }

}

