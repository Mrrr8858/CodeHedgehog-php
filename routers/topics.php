<?php
    function route($method, $urlList, $requesData, $link)
    {
        $token = substr(getallheaders()['Authorization'], 7);
        $user;
        $userIdFromToken = $link->query("SELECT userId FROM tokens WHERE value='$token'")->fetch_assoc();
        $auth;
        if(!is_null($userIdFromToken))
        {
            $userId = $userIdFromToken['userId'];
            $user = $link->query("SELECT * FROM user WHERE id = '$userId'")->fetch_assoc();
            $auth = 1;
        }
        else
        {
            $auth = 0;
        }

        switch($method){
            case 'GET':
                {
                    if(count($urlList) == 1 || !empty($requesData->parameters))
                    { 
                        echo GetInfoAllTopics($link, $requesData->parameters);
                        
                       
                    }
                    else if(count($urlList) == 2 && is_numeric($urlList[1])){
                        
                        echo GetInfoOneTopic($link, $urlList[1]);
                    }
                    else if(count($urlList) == 3 && is_numeric($urlList[1]) && $urlList[2] == "childs")
                    {
                        echo json_encode(GetChilds($link, $urlList[1]));
                    }
                    else
                    {
                        setHTTPStatus("404", "Unknown path");
                    }
                    break;
                }
                
            case 'POST':
                {
                    if($user['roleId'] == 1 && count($urlList) == 1){
                        PostTopic($requesData->body, $link);
                    }
                    else if($user['roleId'] == 1 && is_numeric($urlList[1]) && $urlList[2] == "childs"){
                        PostTopicChilds($requesData->body, $link, $urlList[1]);
                    }
                    else
                    {
                        setHTTPStatus("404", "Unknown path");
                    }
                    break;
                }  
            case 'DELETE':
                {
                    if($user['roleId'] == 1){
                        if(count($urlList) == 2 && is_numeric($urlList[1]))
                        {
                            DeleteTopic($urlList[1], $link);
                        }
                        else if(is_numeric($urlList[1]) && $urlList[2] == "childs")
                        {
                            DeleteTopicsChilds($urlList[1], $link, $requesData->body);
                        }
                    }
                    break;
                }     
            case 'PATCH':
                {
                    if($user['roleId'] == 1)
                    {
                        echo PutchTopic($requesData->body, $urlList[1], $link);
                    }
                    break;
                } 
            default: setHTTPStatus("400", "");
        }
    }

    function GetInfoAllTopics($link, $parameters)
    {
        $message = [];
        if(!is_null($parameters['name'])){
            $pName =  (string)$parameters['name'];
            $res = $link->query("SELECT `id`, `name`, `parentId` FROM `topics` WHERE name = '$pName'"); //как передать строку??? 
        } 
        else if(!is_null($parameters['parent'])){
            $pParent = $parameters['parent'];
            $res = $link->query("SELECT `id`, `name`, `parentId` FROM `topics` WHERE parentId = $pParent");
        } 
        else{
            $res = $link->query("SELECT `id`, `name`, `parentId` FROM `topics`");
        }
       
        if(!$res){
            setHTTPStatus("409", $link->error);
            exit();
        }
        else{
            while($row = $res->fetch_assoc()){
                $message[] = [
                    "id" => $row['id'],
                    "name" => $row['name'],
                    "parentId" => $row['parentId'],
                ];
            }
        }
        return json_encode($message);
    }

    function GetInfoOneTopic($link, $id)
    {
        $topic = $link->query("SELECT `id`, `name`, `parentId` FROM `topics` WHERE id = $id")->fetch_assoc();
        $message = [];
        if(!$topic){
            setHTTPStatus("409", $link->error);
            return;
        }
        else{
            $topicId = $topic['id'];
            $childs = GetChilds($link, $topicId);
            foreach ($topic as $row => $value) {
                $message[$row] = $value;
            } 
            $message["childs"] = $childs;
        }
        return json_encode($message);
    }

    function GetChilds($link, $topicId)
    {
        $childs = [];
        $childRes = $link->query("SELECT `id`, `name`, `parentId` FROM `topics` WHERE parentId = '$topicId'");
        if($childRes){
            while($row = $childRes->fetch_assoc()){
                $childs[] = [
                    "id" => $row['id'],
                    "name" => $row['name'],
                    "parentId" => $row['parentId'],
                ];
            }
        } 
        else
        {
            setHTTPStatus("409", $link->error);
            exit();
        }      
        return $childs; 
    }

    function PutchTopic($body, $topicId, $link)
    {
        $message = [];
        $paramStr = "";
        $validParam = ['name', 'parentId'];
        foreach ($body as $key => $value) {
            if(!in_array($key, $validParam))
            {
                setHTTPStatus("400", "Flied '$key' doesn't exist");
            }
            
                $paramStr .= "`$key` = '$value', ";
            
        }
        $paramStr = rtrim($paramStr, ", ");
        $res = $link->query("UPDATE topics SET $paramStr WHERE id = $topicId");
        
        if(!$res){
            setHTTPStatus("409", $link->error);
            return;
        }
        else{
            $topic = $link->query("SELECT `id`, `name`, `parentId` FROM `topics` WHERE id = '$topicId'")->fetch_assoc();
            if(!$res){
                setHTTPStatus("409", $link->error);
                return;
            }
            else{
                $childs = GetChilds($link, $topicId);
                foreach ($topic as $row => $value) {
                    $message[$row] = $value;
                } 
                $message["childs"] = $childs;
            }
           
        }
        return json_encode($message);
    }

    function PostTopic($body, $link)
    {
        $name = $body->name;
        $parentId = $body->parentId;
        $res;
        if(is_null($parentId)){
            $res = $link->query("INSERT INTO topics(`name`, `parentId`) VALUES ('$name', null)");
        }
        else{
            $res = $link->query("INSERT INTO topics(`name`, `parentId`) VALUES ('$name','$parentId')");
        }
        
        if(!$res){
            setHTTPStatus("409", $link->error);
        }
        else{
            $topic = $link->query("SELECT `id`, `name`, `parentId` FROM `topics` WHERE name = '$name'")->fetch_assoc();
            $topicId = $topic['id'];
            $childs = GetChilds($link, $topicId);
            $message = [];
           
            foreach ($topic as $row => $value) {
                $message[$row] = $value;
            }  
            $message["childs"] = $childs;
            echo json_encode($message);
        }
    }


    function PostTopicChilds($body, $link, $topicId)
    {
        foreach ($body as $row) {
            $childsId = $row;
            $res = $link->query("UPDATE topics SET `parentId`= '$topicId' WHERE id = '$childsId'");
        }  
        
        if(!$res){
            setHTTPStatus("409", $link->error);
            exit();
        }
        else{
            $topic = $link->query("SELECT `id`, `name`, `parentId` FROM `topics` WHERE id = $topicId")->fetch_assoc();
            $childs = GetChilds($link, $topicId);
            $message = [];
           
            foreach ($topic as $row => $value) {
                $message[$row] = $value;
            }  
            $message["childs"] = $childs;
            echo json_encode($message);
        }
    }

    function DeleteTopic($topicId, $link)
    {
        $res = $link->query("DELETE FROM topics WHERE id = $topicId");
        if(!$res){
            setHTTPStatus("409", $link->error);
            exit();
        }
        else{
            $message["message"] =  "OK";
            echo json_encode($message);
        }
    }

    function DeleteTopicsChilds($topicId, $link, $body)
    {
        foreach ($body as $row) {
            $childsId = $row;
            $res = $link->query("UPDATE topics SET `parentId`= null WHERE id = '$childsId'");
            if(!$res){
                setHTTPStatus("409", $link->error);
                exit();
            }
        } 

        $topic = $link->query("SELECT `id`, `name`, `parentId` FROM `topics` WHERE id = $topicId")->fetch_assoc();
        $message = [];
        $childs = GetChilds($link, $topicId);
        foreach ($topic as $row => $value) {
            $message[$row] = $value;
        }  
        $message["childs"] = $childs;
        echo json_encode($message);
        
    }
 ?>   