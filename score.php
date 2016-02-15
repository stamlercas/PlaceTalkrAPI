<?php

function getUserScore($userID)
{
    require_once __DIR__ . '/db.php';
    $db = new DB();

    $db->bind("UserID", $userID);
    $result = $db->query("SELECT 
    (SELECT COUNT(*) * 3
    FROM posts
    WHERE UserID = 2)
    +
    (SELECT COUNT(*) * 2
    FROM comments
    WHERE UserID = 2)
    +
    (SELECT COUNT(*)
    FROM users u
        JOIN posts p ON p.UserID = u.UserID
        JOIN comments c ON c.PostID = p.PostID
    WHERE c.UserID <> u.UserID AND u.UserID = 2) AS Score");

    return $result[0]['Score'];
}