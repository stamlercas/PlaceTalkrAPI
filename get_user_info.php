<?php
    require_once __DIR__ . '/db.php';
    require_once 'score.php';
    $response = array();
    $db = new DB();
    
    if (isset($_POST['UserID']))
    {
        $userID = $_POST['UserID'];
        $db->bind('UserID', $userID);
        $result = $db->query('SELECT UserID, Username, FirstName, LastName, Email '
                . 'FROM users '
                . 'WHERE UserID = :UserID');
        
        $db->bind('UserID', $userID);
        $numPosts = $db->query('SELECT COUNT(*) AS COUNT '
                . 'FROM posts '
                . 'WHERE UserID = :UserID');
        
        $db->bind('UserID', $userID);
        $numComments = $db->query('SELECT COUNT(*) AS COUNT '
                . 'FROM comments '
                . 'WHERE UserID = :UserID');
        
        $score = getUserScore($userID);
        
        $db->bind('UserID', $userID);
        $mostPopPlace = $db->query('SELECT COUNT(*) AS COUNT, places.Name '
                . 'FROM posts '
                . 'JOIN users on posts.UserID = users.UserID '
                . 'JOIN places on posts.PlaceID = places.PlaceID '
                . 'WHERE posts.UserID = :UserID '
                . 'GROUP BY posts.PlaceID '
                . 'ORDER BY COUNT DESC LIMIT 1');
        
        if (!empty($result) && !empty($numPosts) && !empty($numComments))
        {
            $response['success'] = 1;
            $response['message'] = "User info grabbed successfully.";
            
            $response['UserInfo'] = array();
            $info = array();
            $info['UserID'] = $result[0]['UserID'];
            $info['Username'] = $result[0]['Username'];
            $info['FirstName'] = $result[0]['FirstName'];
            $info['LastName'] = $result[0]['LastName'];
            $info['Email'] = $result[0]['Email'];
            $info['NumberOfPosts'] = $numPosts[0]['COUNT'];
            $info['NumberOfComments'] = $numComments[0]['COUNT'];
            $info['Score']  = $score;
            $info['MostPopularPlace'] = $mostPopPlace[0]['Name'];
            
            array_push($response['UserInfo'], $info);
            echo json_encode($response);
        }
        else
        {
            $response['success'] = 0;
            $response['message'] = 'No user found.';
            echo json_encode($response);
        }
    }
    else
    {
        $response['success'] = 0;
        $response['message'] = 'Required field(s) are missing';
        echo json_encode($response);
    }

