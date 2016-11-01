<?php
    class JSONResponse
    {
        public function __construct()
        {
            
        }
        
        //typical response
        public function response($success, $msg, array $response)
        {
            $response['success'] = $success;
            $response['message'] = $msg;
            
            echo json_encode($response);
        }
    }

