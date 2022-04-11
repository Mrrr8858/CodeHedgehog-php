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
                       echo GetInfoRoles($link);
                    }
                    else if(count($urlList) == 2 && is_numeric($urlList[1]))
                    {
                        echo GetInfoOneRole($link, $urlList[1]);
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

    function GetInfoRoles($link)
    {
        $message = [];
        $res = $link->query("SELECT roleId, name FROM roles ORDER BY roleId ASC");
        if(!$res){
            setHTTPStatus("409", $link->error);
            return;
        }
        else{
            while($row = $res->fetch_assoc()){
                $message[] = [
                    "roleId" => $row['roleId'],
                    "name" => $row['name'],
                ];
            }
        }
        return json_encode($message);
    }

    function GetInfoOneRole($link, $roleId)
    {
        $message = [];
        $res = $link->query("SELECT roleId, name FROM roles WHERE roleId = $roleId");
        if(!$res){
            setHTTPStatus("409", $link->error);
            return;
        }
        else{
            while($row = $res->fetch_assoc()){
                $message = [
                    "roleId" => $row['roleId'],
                    "name" => $row['name'],
                ];
            }
        }
        return json_encode($message);
    }

 ?>   