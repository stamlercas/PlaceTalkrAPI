<?php
    require_once __DIR__ . '/db.php';
    require_once 'JSONResponse.php';
    $response = array();
    
    if (isset($_POST['UserID']) && isset($_POST['FriendID']))
    {
        $userID = $_POST['UserID'];
        $friendID = $_POST['FriendID'];
        $db = new DB();
        $db->bind('UserID', $userID);
        $db->bind('FriendID', $friendID);
        $result = $db->query("INSERT INTO `friends` (UserOneID, UserTwoID, Status, ActionUserID) "
                . "VALUES (:UserID, :FriendID, 'Pending', :UserID)");
        if ($result)
        {
            JSONResponse::resposne(1, 'Friend Request successfully created.', $response);
        }
        else
        {
            JSONResponse::response(0, 'Sorry, an error occurred.', $response);
        }
        
    }
    else
    {
        JSONResponse::response(0, 'Required field(s) are missing.', $response);
    }

