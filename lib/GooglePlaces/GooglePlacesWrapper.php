<?php

class GooglePlaces
{
    private $apikey;
    
    function __construct()
    {
        $this->apikey = "AIzaSyAvfsiRwpIHmqqNK4-gatCu4YXRLGOlzr0";
    }
    
    function getPlaceById($id)
    {
        $json = file_get_contents(
                "https://maps.googleapis.com/maps/api/place/details/json?placeid=$id&key=$this->apikey");
        $obj = json_decode($json, true);
        
        //echo $json;
        
        if ($obj['status'] == "OK")
        {
            return $obj['result'];
        }
    }
}

//$gp = new GooglePlaces();
//$gp->getPlaceById('ChIJ62hm7Bn0zIkRCSglEymLKnM');