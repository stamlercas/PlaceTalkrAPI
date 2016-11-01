<?php
    function getLimit()
    {
        if (isset($_GET['limit']))
        {
            return $_GET['limit'];
        }
        else
        {
            return 25;
        }
    }
    
    function getOffset()
    {
        if (isset($_GET['offset']))
        {
            return $_GET['offset'];
        }
        else
        {
            return 0;
        }
    }
?>