<?php

class Model
{
    protected $db;
    
    function __construct()
    {
        $this->db = new DB();
    }
    
    public function getComments($postID, $offset, $limit)
    {
        $this->db->bind('PostID', $postID);
        $this->db->bind('Offset', $offset);
        $this->db->bind('Limit', $limit);
        return $this->db->query("SELECT CommentID, c.Content, c.Time, u.Username, u.UserID "
                . "FROM comments c "
                . "JOIN users u on u.userID = c.UserID "
                . "WHERE PostID = :PostID "
                . "ORDER BY c.Time ASC "
                . "LIMIT :Offset, :Limit");
    }
    
    public function getPlace($placeID)
    {
        $this->db->bind('PlaceID', $placeID);
        $place = $this->db->query("SELECT Name, Address, PhoneNumber, WebsiteUri FROM places WHERE PlaceID = :PlaceID");
        return $place[0];
    }
    
    public function getPost($postID)
    {
        $this->db->bind('PostID', $postID);
        $result = $this->db->query("SELECT PostID, Content, Time, users.Username, users.UserID FROM posts "
                . "JOIN users ON posts.userID = users.userID WHERE PostID = :PostID ORDER BY Time DESC");
        return $result[0];
    }
    
    public function getPosts($placeID, $offset, $limit)
    {
        $this->db->bind('PlaceID', $placeID);
        $this->db->bind('StartFrom', $offset);
        $this->db->bind('PageSize', $limit);
        return $this->db->query("SELECT PostID, Content, Time, posts.UserID, users.Username, places.Name AS Name, "
                . "(SELECT COUNT(*) FROM comments WHERE posts.PostID = PostID) AS NumberOfComments "
                . "FROM posts "
                . "JOIN users ON posts.UserID = users.UserID "
                . "JOIN places ON places.PlaceID = posts.PlaceID "
                . "WHERE posts.PlaceID = :PlaceID "
                . "ORDER BY Time DESC "
                . "LIMIT :StartFrom, :PageSize");
    }
    
    public function getUserInfo($userID)
    {
        $this->db->bind('UserID', $userID);
        $result = $this->db->query('SELECT UserID, Username, FirstName, LastName, Email '
                . 'FROM users '
                . 'WHERE UserID = :UserID');
        return $result[0];
    }
    
    public function getUserScore($userID)
    {
        //each parameter must be unique
        $this->db->bind("UserID", $userID);
        $this->db->bind("UserID1", $userID);
        $this->db->bind("UserID2", $userID);
        $result = $this->db->query("SELECT 
        (SELECT COUNT(*) * 3
        FROM posts
        WHERE UserID = :UserID)
        +
        (SELECT COUNT(*) * 2
        FROM comments
        WHERE UserID = :UserID1)
        +
        (SELECT COUNT(*)
        FROM users u
            JOIN posts p ON p.UserID = u.UserID
            JOIN comments c ON c.PostID = p.PostID
        WHERE c.UserID <> u.UserID AND u.UserID = :UserID2) AS Score");

        return $result[0]['Score'];
    }
    
    public function getUsersMostPopularPlace($userID)
    {
        $this->db->bind('UserID', $userID);
        $mostPopPlace = $this->db->query('SELECT COUNT(*) AS COUNT, places.Name '
                . 'FROM posts '
                . 'JOIN users on posts.UserID = users.UserID '
                . 'JOIN places on posts.PlaceID = places.PlaceID '
                . 'WHERE posts.UserID = :UserID '
                . 'GROUP BY posts.PlaceID '
                . 'ORDER BY COUNT DESC LIMIT 1');
        if ($mostPopPlace != null)
            return $mostPopPlace[0];
        else
            return null;
    }
    
    public function insertComment($content, $userID, $postID)
    {   
        $this->db->bind('Content', $content);
        $this->db->bind('UserID', $userID);
        $this->db->bind('PostID', $postID);
        
        return $this->db->query("INSERT INTO comments(Content, UserID, PostID) VALUES (:Content, :UserID, :PostID)");
    }
    
    public function insertPlace($placeID)
    {
        $googlePlaces = new GooglePlaces();
        $place = $googlePlaces->getPlaceById($placeID);
        
        $this->db->bind('PlaceID', $placeID);
        $this->db->bind('Name', $place['name']);
        $this->db->bind('Longitude', $place['geometry']['location']['lng']);
        $this->db->bind('Latitude', $place['geometry']['location']['lat']);
        //these fields can be null
        //address
        if (isset($place['formatted_address']))
        {
            $this->db->bind('Address', $place['formatted_address']);
        }
        else
        {
            $this->db->bind('Address', null);
        }
        //phone number
        if (isset($place['formatted_phone_number']))
        {
            $this->db->bind('PhoneNumber', $place['formatted_phone_number']);
        }
        else
        {
            $this->db->bind('PhoneNumber', null);
        }
        
        //website uri
        if (isset($place['website']))
        {
            $this->db->bind('WebsiteUri', $place['website']);
        }
        else
        {
            $this->db->bind('WebsiteUri', null);
        }
        return $this->db->query('INSERT INTO `places` (PlaceID, Name, Address, Longitude, Latitude, PhoneNumber, WebsiteUri, Updated) '
                . 'VALUES(:PlaceID, :Name, :Address, :Longitude, :Latitude, :PhoneNumber, :WebsiteUri, Updated = "Y")');
    }
    
    public function insertPost($content, $userID, $placeID)
    {
        $this->db->bind('Content', $content);
        $this->db->bind('UserID', $userID);
        $this->db->bind('PlaceID', $placeID);
        
        return $this->db->query("INSERT INTO posts(Content, UserID, PlaceID) VALUES (:Content, :UserID, :PlaceID)");
    }
    
    public function insertUser($fname, $lname, $username, $password, $email)
    {
        $db->bind('FirstName', $fname);
        $db->bind('LastName', $lname);
        $db->bind('Username', $username);
        $db->bind('Password', $password);
        $db->bind('Email', $email);

        return $db->query("INSERT INTO `users`(FirstName, LastName, Username, Password, Email) "
                . "VALUES (:FirstName, :LastName, :Username, :Password, :Email)");
    }
    
    public function login($username, $password)
    {
        $this->db->bind('Username', $username);
        $this->db->bind('Password', $password);
        
        $result = $this->db->query("SELECT UserID, FirstName, LastName, Username, Password, Email FROM users "
                . "WHERE Username = :Username AND Password = :Password");
        
        return $result;
    }
    
    public function numberOfComments($postID)
    {
        $this->db->bind('PostID', $postID);
        $result =  $this->db->query("SELECT COUNT(*) AS Count "
                . "FROM comments c "
                . "JOIN users u on u.userID = c.UserID "
                . "WHERE PostID = :PostID ");
        return $result[0]['Count'];
    }
    
    public function numberOfCommentsByUser($userID)
    {
        $this->db->bind('UserID', $userID);
        $numComments = $this->db->query('SELECT COUNT(*) AS COUNT '
                . 'FROM comments '
                . 'WHERE UserID = :UserID');
        return $numComments[0];
    }
    
    public function numberOfPosts($placeID)
    {
        $this->db->bind('PlaceID', $placeID);
        $numPosts = $this->db->query("SELECT COUNT(*) AS Count "
                . "FROM posts "
                . "JOIN users ON posts.UserID = users.UserID "
                . "JOIN places ON places.PlaceID = posts.PlaceID "
                . "WHERE posts.PlaceID = :PlaceID ");
        return $numPosts[0]['Count'];
    }
    
    public function numberOfPostsByUser($userID)
    {
        $this->db->bind('UserID', $userID);
        $numPosts = $this->db->query('SELECT COUNT(*) AS COUNT '
                . 'FROM posts '
                . 'WHERE UserID = :UserID');
        return $numPosts[0];
    }
    
    public function placeUpdated($placeID)
    {
        $this->db->bind('PlaceID', $placeID);
        $num = $this->db->query('SELECT Updated FROM  `places` WHERE PlaceID = :PlaceID');
        if($num[0]['Updated'] == 'Y')
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function updatePlace($placeID)
    {
        $googlePlaces = new GooglePlaces();
        $place = $googlePlaces->getPlaceById($placeID);
        
        $this->db->bind('PlaceID', $placeID);
        //these fields can be null
        //address
        if (isset($place['formatted_address']))
        {
            $this->db->bind('Address', $place['formatted_address']);
        }
        else
        {
            $this->db->bind('Address', null);
        }
        //phone number
        if (isset($place['formatted_phone_number']))
        {
            $this->db->bind('PhoneNumber', $place['formatted_phone_number']);
        }
        else
        {
            $this->db->bind('PhoneNumber', null);
        }
        
        //website uri
        if (isset($place['website']))
        {
            $this->db->bind('WebsiteUri', $place['website']);
        }
        else
        {
            $this->db->bind('WebsiteUri', null);
        }
        return $this->db->query('UPDATE `places` '
                . 'SET Address = :Address, PhoneNumber = :PhoneNumber, WebsiteUri = :WebsiteUri, Updated = "Y" '
                . 'WHERE PlaceID = :PlaceID');
    }
}