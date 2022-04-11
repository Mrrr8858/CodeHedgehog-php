<?php
    function route($method, $urlList, $requesData, $link)
    {
        $token = substr(getallheaders()['Authorization'], 7);
        $user;
        $userIdFromToken = $link->query("SELECT userId FROM tokens WHERE value='$token'")->fetch_assoc();
                    
        if(!is_null($userIdFromToken))
        {
            $userId = $userIdFromToken['userId'];
            $user = $link->query("SELECT * FROM user WHERE id = '$userId'")->fetch_assoc();
        }
        else
        {
            setHTTPStatus("400", "Token not exist");
            exit();
        }
        switch($method){
            case 'POST':
                {
                    $userId = $user['id'];
                    $token = $link->query("DELETE FROM `tokens` WHERE id = '$userId'")->fetch_assoc();
                    if(!$token)
                    {
                        setHTTPStatus("403", "");
                    }
                }  
        }
    }


 ?>   