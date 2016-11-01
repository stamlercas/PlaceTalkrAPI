<?php

    include 'generalFuncs.php';
    include 'howLongAgo.php';
    include 'JSONResponse.php';
    include 'GooglePlaces/GooglePlacesWrapper.php';
    
    //MODEL
    require DIR_MODEL . '/model.php';
    require DIR_MODEL . '/existsModel.php';
    //DB
    require(DIR_DB . "/Log.php");
    require DIR_DB . "/db.php";
?>