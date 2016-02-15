<?php
    /*
    $_POST['PlaceID'] = 1;
    $_POST['Name'] = '';
    $_POST['Longitude'] = '';
    $_POST['Latitude'] = '';
    $_POST['Page'] = 1;
    $_POST['PageSize'] = 25;
    */

    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/lib/generalFuncs.php';
    //array for json response
    $response = array();
    
    if (isset($_POST['PlaceID']) && isset($_POST['Name']) && isset($_POST['Longitude']) && isset($_POST['Latitude'])
            && isset($_POST['Page']) && isset($_POST['PageSize']))
    {
        $placeID = $_POST['PlaceID'];
        $name = $_POST['Name'];
        $longitude = $_POST['Longitude'];
        $latitude = $_POST['Latitude'];
        $page = $_POST['Page'];
        $pageSize = $_POST['PageSize'];
        
        
        require_once 'ifExists.php';
        $db = new DB();
        //if place doesn't exist, make it exist
        if (!placeExists($placeID))
        {
            $db->bind('PlaceID', $placeID);
            $db->bind('Name', $name);
            $db->bind('Longitude', $longitude);
            $db->bind('Latitude', $latitude);
            $db->query('INSERT INTO `places` (PlaceID, Name, Longitude, Latitude) '
                    . 'VALUES(:PlaceID, :Name, :Longitude, :Latitude)');
        }
        
        //page decides where to start from based on page size
        $startFrom = $page * $pageSize;
        $db->bind('PlaceID', $placeID);
        $db->bind('StartFrom', $startFrom);
        $db->bind('PageSize', $pageSize);
        $results = $db->query("SELECT PostID, Content, Time, posts.UserID, users.Username, places.Name AS Name "
                . "FROM posts "
                . "JOIN users ON posts.UserID = users.UserID "
                . "JOIN places ON places.PlaceID = posts.PlaceID "
                . "WHERE posts.PlaceID = :PlaceID "
                . "ORDER BY Time DESC "
                . "LIMIT :StartFrom, :PageSize");
        
        $db->bind('PlaceID', $placeID);
        $name = $db->query("SELECT Name FROM places WHERE PlaceID = :PlaceID");
        
        $db->bind('PlaceID', $placeID);
        $numPosts = $db->query("SELECT COUNT(*) AS Count "
                . "FROM posts "
                . "JOIN users ON posts.UserID = users.UserID "
                . "JOIN places ON places.PlaceID = posts.PlaceID "
                . "WHERE posts.PlaceID = :PlaceID ");
        
        if ($startFrom + $pageSize > $numPosts[0]['Count']) {
            $endOfList = true;
        } else {
            $endOfList = false;
        }
        
        //results is not empty
        //the only time, there can be no posts to a place is when they are on the first page
        if (count($results) != 0)
        {
            //successful
            $response['success'] = 1;
            $response['message'] = "Posts successfully grabbed.";
            $response['PlaceName'] = $name[0]['Name'];
            $response['FirstToPost'] = 0;
            $response['EndOfList'] = $endOfList;

            //putting results into json object
            $response['posts'] = array();
            foreach($results as $row)
            {
                $post = array();
                $post['PostID'] = $row['PostID'];
                $post['Content'] = $row['Content'];

                $time = ago(strtotime($row['Time']));
                $post['Time'] = $time;
                $post['UserID'] = $row['UserID'];
                $post['Username'] = $row['Username'];
                array_push($response['posts'], $post);
            }

            //return json
            echo json_encode($response);
        }
        //this is when you hit the end of the list
        else if (count($results) == 0 && $page != 0)
        {
            //successful
            $response['success'] = 1;
            $response['message'] = "Posts successfully grabbed. End of List";
            $response['PlaceName'] = $name[0]['Name'];
            $response['FirstToPost'] = 0;
            $response['EndOfList'] = true;
        }
        else
        {
            //posts are empty
            $response['success'] = 1;
            $response['message'] = 'No posts have been made.';
            $response['PlaceName'] = $name[0]['Name'];
            $response['FirstToPost'] = 1;       //no posts have been made
            $response['EndOfList'] = false;
            echo json_encode($response);
        }
    }
    else
    {
        //error
        $response['success'] = 0;
        $response['message'] = 'Required field(s) are missing.';
        echo json_encode($response);
    }
?>

