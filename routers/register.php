<?php
    function route($method, $urlList, $requesData, $link)
    {
        switch($method){
            case 'POST':
            {
                $password = hash("sha1", $requesData->body->password);
                $name = $requesData->body->name;
                $surname = $requesData->body->surname;
                $username = $requesData->body->username;
                $userInsert = $link->prepare("INSERT INTO user (name, surname, username, password) VALUES (?, ?, ?, ?)");
                $userInsert->bind_param("ssss", $name, $surname, $username, $password);
                $userInsert->execute();
                $error = $userInsert->error;
                
                if(!empty($error)){
                    setHTTPStatus("409", "User with same username already exists");
                }
                else{
                    setHTTPStatus("200", "");
                } 
                break;
            }  
            default: setHTTPStatus("400", "");
        }
    }


 ?>   