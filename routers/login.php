<?php
    function route($method, $urlList, $requesData, $link)
    {
        switch($method){
            case 'POST':
                {
                    $username = $requesData->body->username;
                    $password = hash("sha1", $requesData->body->password);
                    $user = $link->query("SELECT id FROM user WHERE username='$username' AND password = '$password'")->fetch_assoc();
                    
                    if(!is_null($user))
                    {
                        $token = bin2hex(random_bytes(16));
                        $userId = $user['id'];
                        $tokenInsert = $link->query("INSERT INTO tokens (value, userId) VALUES ('$token','$userId')");
                        if(!$tokenInsert){
                            echo $link->error;
                        }
                        else{
                            echo json_encode(['token' => $token]);
                        }
                    }
                    else
                    {
                        setHTTPStatus("400", "Input data incorrect");
                    }
                }  
            default: setHTTPStatus("400", "");
        }
    }


 ?>   