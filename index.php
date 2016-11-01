<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

define('DIR_WEB', dirname(__FILE__));
define('DIR_VENDOR', DIR_WEB . '/vendor');
define('DIR_MODEL', DIR_WEB . '/model');
define('DIR_DB', DIR_WEB . '/db');
define('DIR_LIB', DIR_WEB . '/lib');

require DIR_LIB . '/autoload.php';
require DIR_VENDOR . '/autoload.php';

$app = new \Slim\App();   //instantiating slim app

//when no specific function is called
$app->get('/', function() use($app) {
    //$app->response->setStatus(200);
    $response = array();
    $response['success'] = 1;
    $response['message'] = "Welcome to the PlaceTalkr API";
    echo json_encode($response);
});

/***************************
 * COMMENTS
***************************/
//create comment on post
$app->post('/comments', 'createComment');

/***************************
 * LOGIN
***************************/
//login user
$app->post('/login', 'login');

/***************************
 * POSTS
***************************/
//get single post w/ comments
//not using a plural, because you are using the post id to return one SINGLE post
$app->get('/post/{id}', 'getPost');
//create post in place
$app->post('/posts', 'createPost');
//get all posts within a certain place
$app->get('/posts/{placeID}', 'getPosts');

/***************************
 * REGISTER
***************************/
//register account
$app->post('/register', 'register');

/***************************
 * USERS
***************************/
//get user info
$app->get('/users/{id}', 'getUserInfo');


//actually run the app..
$app->run();
/**************************
 * FUNCTIONS
**************************/

/****************
 * GET functions
****************/
// ----- LOGIN -----
function login(Request $request)
{
    if (isset($_POST['Username']) && isset($_POST['Password']))
    {
        $username = $_POST["Username"];
        $password = $_POST['Password'];
        
        $model = new Model();
        $result = $model->login($username, $password);

        if ($result)
        {
            $response['success'] = 1;
            $response['user'] = array();
            array_push($response['user'], $result);
            echo json_encode($response);
        }
        else
        {
            $response['success'] = 0;
            $response['message'] = "An error has occurred.";
            echo json_encode($response);
        }
    }
    else
    {
        $response['success'] = 0;
        $resposne['message'] = 'Required field(s) are missing.';
        echo json_encode($response);
    }
}

// ----- POSTS -----
function getPost(Request $request)
{
    $db = new DB();
    $model = new Model();
    
    $postID = $request->getAttribute('id');
    if (isset($postID))
    {
        $offset = getOffset();
        $limit = getLimit();
        
        $result = $model->getPost($postID);     //the content of the post
        
        $comments = $model->getComments($postID, $offset, $limit);  //the comments on the post

        $numComments = $model->numberOfComments($postID);     //the number of comments
        $startFrom = $offset * $limit;
        if ($startFrom + $limit >= $numComments) {
            $endOfList = true;
        } else {
            $endOfList = false;
        }
        
        //if result actually contains something
        if (!empty($result))
        {
            //should only be one row
            if ($result != false)
            {
                $response['success'] = 1;
                $response['message'] = 'Post successfully grabbed.';
                $response['post'] = array();
                $post = array();
                $post['PostID'] = $result['PostID'];
                $post['Content'] = $result['Content'];
                //$post['Time'] = ago(date('F j, Y g:i a', strtotime($result[0]['Time'])));
                $post['Time'] = ago(strtotime($result['Time']));
                $post['Username'] = $result['Username'];
                $post['UserID'] = $result['UserID'];
                
                
                array_push($response['post'], $post);
                
                $response['comments'] = array();
                foreach($comments as $row)
                {
                    $comment = array();
                    
                    $comment['CommentID'] = $row['CommentID'];
                    $comment['Content'] = $row['Content'];
                    $comment['Time'] = ago(strtotime($row['Time']));
                    $comment['Username'] = $row['Username'];
                    $comment['UserID'] = $row['UserID'];
                    
                    array_push($response['comments'], $comment);
                }
                
                if (count($comments) == 0)
                {
                    $response['FirstToComment'] = 1;
                }
                else
                {
                    $response['FirstToComment'] = 0;
                }
                $response['EndOfList'] = $endOfList;
                
                echo json_encode($response);
            }
            //this is when you hit the end of the list
            else if (count($result) == 0 && $offset != 0)
            {
                //successful
                $response['success'] = 1;
                $response['message'] = "Comments successfully grabbed. End of List";
                $response['FirstToPost'] = 0;
                $response['EndOfList'] = true;
            }
            else
            {
                //not found
                $response['success'] = 0;
                $response['message'] = 'No post found.';
                $response['FirstToPost'] = 1;       //no posts have been made
                $response['EndOfList'] = $endOfList;
                echo json_encode($response);
            }
        }
        else
        {
            $response['success'] = 0;
            $response['message'] = 'No post found.';
            $response['FirstToPost'] = 1;       //no posts have been made
            $response['EndOfList'] = $endOfList;
            echo json_encode($response);
        }
    }
    else
    {
        //missing id
        $response['success'] = 0;
        $response['message'] = 'Required field(s) is missing.';
        echo json_encode($response);
    }
}

function getPosts(Request $request)
{
    $response = array();
    $model = new Model();
    
    $placeID = $request->getAttribute('placeID');
    
    if (isset($placeID))
    {
        $offset = getOffset();
        $limit = getLimit();
        
        //if place doesn't exist, make it exist
        $existsModel = new ExistsModel();
        if (!$existsModel->placeExists($placeID))
        {
            $model->insertPlace($placeID);
        }
        else if (!$model->placeUpdated($placeID))
        {
            $model->updatePlace($placeID);
        }
        
        //page decides where to start from based on page size
        $startFrom = $offset * $limit;
        $results = $model->getPosts($placeID, $offset, $limit);
        
        $placeInfo = $model->getPlace($placeID);
        
        $numPosts = $model->numberOfPosts($placeID);
        
        if ($startFrom + $limit >= $numPosts) {
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
            $response['PlaceName'] = $placeInfo['Name'];
            $response['Address'] = $placeInfo['Address'];
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
                
                $post['NumberOfComments'] = $row['NumberOfComments'];
                if ($row['NumberOfComments'] == 1) {
                    $post['NumberOfComments'] .= " comment";
                }
                else {
                    $post['NumberOfComments'] .= " comments";
                }
                array_push($response['posts'], $post);
            }

            //return json
            echo json_encode($response);
        }
        //this is when you hit the end of the list
        else if (count($results) == 0 && $offset != 0)
        {
            //successful
            $response['success'] = 1;
            $response['message'] = "Posts successfully grabbed. End of List";
            $response['PlaceName'] = $placeInfo['Name'];
            $response['FirstToPost'] = 0;
            $response['EndOfList'] = true;
        }
        else
        {
            //posts are empty
            $response['success'] = 1;
            $response['message'] = 'No posts have been made.';
            $response['PlaceName'] = $placeInfo['Name'];
            $response['Address'] = $placeInfo['Address'];
            $response['FirstToPost'] = 1;       //no posts have been made
            $response['EndOfList'] = $endOfList;
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
}

// ----- USER -----
function getUserInfo(Request $request)
{
    $userID = $request->getAttribute('id');
    
    $model = new Model();
    
    if (isset($userID))
    {
        $result = $model->getUserInfo($userID);
        
        $numPosts = $model->numberOfPostsByUser($userID);
        
        $numComments = $model->numberOfCommentsByUser($userID);
        
        $score = $model->getUserScore($userID);
        
        $mostPopPlace = $model->getUsersMostPopularPlace($userID);
        
        if (!empty($result) && !empty($numPosts) && !empty($numComments))
        {
            $response['success'] = 1;
            $response['message'] = "User info grabbed successfully.";
            
            $response['UserInfo'] = array();
            $info = array();
            $info['UserID'] = $result['UserID'];
            $info['Username'] = $result['Username'];
            $info['FirstName'] = $result['FirstName'];
            $info['LastName'] = $result['LastName'];
            $info['Email'] = $result['Email'];
            $info['NumberOfPosts'] = $numPosts['COUNT'];
            $info['NumberOfComments'] = $numComments['COUNT'];
            $info['Score']  = $score;
            @$info['MostPopularPlace'] = $mostPopPlace['Name'];  //can be null, gives notice of undefined offset
            
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
}

/****************
 * POST functions
****************/
//create comment on post
function createComment(Request $request)
{
    //response for JSON
    $response = array();
    
    $model = new Model();
    
    if (isset($_POST['Content']) && isset($_POST['UserID']) && isset($_POST['PostID']))
    {
        $content = $_POST['Content'];
        $userID = $_POST['UserID'];
        $postID = $_POST['PostID'];
        
        $result = $model->insertComment($content, $userID, $postID);
        
        if ($result)
        {
            //successfully inserted into db
            $response['success'] = 1;
            $response['message'] = 'Comment successfully created';
            $response['PostID'] = $postID;
            
            //JSON response
            echo json_encode($response);
        }
        else
        {
            //failed to insert
            $response['success'] = 0;
            $response['message'] = 'An error occurred.';
            
            //JSON response
            echo json_encode($response);
        }
    }
    else 
    {
        //missing fields
        $response['success'] = 0;
        $response['message'] = 'Required field(s) are missing.';
        
        echo json_encode($response);
    }
}

//create post in place
function createPost()
{
    //response for JSON
    $response = array();
    
    $model = new Model();
    
    if (isset($_POST['Content']) && isset($_POST['UserID']) && isset($_POST['PlaceID']))
    {
        $content = $_POST['Content'];
        $userID = $_POST['UserID'];
        $placeID = $_POST['PlaceID'];
        
        $result = $model->insertPost($content, $userID, $placeID);
        
        if ($result)
        {
            //successfully inserted into db
            $response['success'] = 1;
            $response['message'] = 'Post successfully created';
            
            //JSON response
            echo json_encode($response);
        }
        else
        {
            //failed to insert
            $response['success'] = 0;
            $response['message'] = 'An error occurred.';
            
            //JSON response
            echo json_encode($response);
        }
    }
    else 
    {
        //missing fields
        $response['success'] = 0;
        $response['message'] = 'Required field(s) are missing.';
        
        echo json_encode($response);
    }
}

//register account
function register(Request $request)
{
    //response for json
    $response = array();
    
    $model = new Model();
    $existsModel = new ExistsModel();
    
    if (isset($_POST['FirstName']) && isset($_POST['LastName']) && isset($_POST['Username']) && isset($_POST['Password'])
            && isset($_POST['Email']))
    {
        $fname = $_POST['FirstName'];
        $lname = $_POST['LastName'];
        $username = $_POST['Username'];
        $password = $_POST['Password'];
        $email = $_POST['Email'];
        
        require_once __DIR__. '/ifExists.php';
        if (!$existsModel->usernameExists($username))
        {
            if (!$existsModel->emailExists($email))
            {
        
                $result = $model->insertUser($fname, $lname, $username, $password, $email);
                
                if ($result)
                {
                    //successful
                    $response["success"] = 1;
                    $response["message"] = "User successfully registered.";

                    echo json_encode($response);
                }
                else
                {
                    $response["success"] = 0;
                    $response["message"] = 'An error occurred.';

                    echo json_encode($response);
                }
            }
            else
            {
                $response['success'] = 0;
                $response['message'] = 'Email already exists';
                
                echo json_encode($response);
            }
        }
        else 
        {
            $response['success'] = 0;
            $response['message'] = 'Username already exists';
            
            echo json_encode($response);
        }
    }
    else 
    {
        //missing fields
        $response['success'] = 0;
        $response['message'] = 'Required field(s) are missing.';
        
        echo json_encode($response);
    }
}