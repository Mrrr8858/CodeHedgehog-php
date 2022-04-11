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
            case 'GET':
                {
                    if(count($urlList) == 1)
                    { 
                        if($user['roleId'] == 1)
                        {
                            echo GetInfoUsers($link);
                        }
                        else
                        {
                            setHTTPStatus("403", "Available only for admin");
                        }
                       
                    }
                    else if(count($urlList) == 2 && is_numeric($urlList[1]))
                    {
                        if($user['roleId'] == 1 || $urlList[1] == $user['id'])
                        {
                            echo GetInfoOneUser($link, $urlList[1]);
                        }
                        else
                        {
                            setHTTPStatus("403", "Available only for admin or user");
                        }
                    }
                    else
                    {
                        setHTTPStatus("404", "Unknown path");
                    }
                    break;
                }
                
            case 'POST':
                {
                    if(count($urlList) == 3 && $urlList[2] == "role" && is_numeric($urlList[1])){
                        if($user['roleId'] == 1)
                        {
                            PostRoleUser($requesData->body, $urlList[1], $link);
                        }
                        else
                        {
                            setHTTPStatus("403", "Available only for admin");
                        }
                    }
                    else
                    {
                        setHTTPStatus("404", "Unknown path");
                    }
                    break;
                }  
            case 'DELETE':
                {
                    if (is_numeric($urlList[1]))
                    {
                        if($user['roleId'] == 1)
                        {
                            DeleteUser($urlList[1], $link);
                        }
                        else
                        {
                            setHTTPStatus("403", "Available only for admin");
                        }
                    }
                    else
                    {
                        setHTTPStatus("404", "Unknown path");
                    }
                    break;
                }     
            case 'PATCH':
                {
                    if (is_numeric($urlList[1]))
                    {
                        if($user['roleId'] == 1)
                        {
                            echo PutchUser($requesData->body, $urlList[1], $link);
                        }
                        else
                        {
                            setHTTPStatus("403", "Available only for admin");
                        }
                    }
                    else
                    {
                        setHTTPStatus("404", "Unknown path");
                    }
                    break;
                } 
            default: setHTTPStatus("400", "");
        }
    }

    function GetInfoUsers($link)
    {
        $message = [];
        $res = $link->query("SELECT id, username, roleId FROM user ORDER BY id ASC");
        if(!$res){
            setHTTPStatus("409", $link->error);
            return;
        }
        else{
            while($row = $res->fetch_assoc()){
                $message[] = [
                    "userId" => $row['id'],
                    "username" => $row['username'],
                    "roleId" => $row['roleId'],
                ];
            }
        }
        return json_encode($message);
    }

    function GetInfoOneUser($link, $userId)
    {
        $message = [];
        $res = $link->query("SELECT id, username, roleId, surname, name FROM user WHERE id = $userId");
        if(!$res){
            setHTTPStatus("409", $link->error);
            return;
        }
        else{
            while($row = $res->fetch_assoc()){
                $message = [
                    "userId" => $row['id'],
                    "username" => $row['username'],
                    "roleId" => $row['roleId'],
                    "name" => $row['name'],
                    "surname" => $row['surname'],
                ];
            }
        }
        return json_encode($message);
    }

    function PutchUser($body, $userId, $link) 
    {
        $message = [];
        $paramStr = "";
        $validParam = ['password', 'username', 'name', 'surname'];
        foreach ($body as $key => $value) {
            if(!in_array($key, $validParam))
            {
                setHTTPStatus("400", "Flied '$key' doesn't exist");
                exit();
            }
            if($key == "password")
            {
                $pass = hash("sha1", $value);
                $paramStr .= "`$key` = '$pass', ";
            }
            else
            {
                $paramStr .= "`$key` = '$value', ";
            }
        }
        $paramStr = rtrim($paramStr, ', ');
        $res = $link->query("UPDATE user SET $paramStr WHERE id = $userId");
        
        if(!$res){
            setHTTPStatus("409", $link->error);
            return;
        }
        else{
            $res = $link->query("SELECT id, username, roleId, surname, name FROM user WHERE id =  $userId");
            if(!$res){
                setHTTPStatus("409", $link->error);
                return;
            }
            else{
                while($row = $res->fetch_assoc()){
                    $message[] = [
                        "userId" => $row['id'],
                        "username" => $row['username'],
                        "roleId" => $row['roleId'],
                        "name" => $row['name'],
                        "surname" => $row['surname'],
                    ];
                }
            }
           
        }
        return json_encode($message);
    }


    function PostRoleUser($body, $userId, $link)
    {
        $res = $link->query("UPDATE user SET roleId='$body->roleId' WHERE id = $userId");
        if(!$res){
            setHTTPStatus("409", $link->error);
            return;
        }
        else{
            $message["message"] =  "OK";
            echo json_encode($message);
        }
    }

    function DeleteUser($userId, $link)
    {
        $res = $link->query("DELETE FROM user WHERE id = $userId");
        if(!$res){
            setHTTPStatus("409", $link->error);
            return;
        }
        else{
            $message["message"] =  "OK";
            echo json_encode($message);
        }
    }
 ?>   