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
                        echo GetInfoAllTasks($link, $requesData->parameters);
                    }
                    else if(count($urlList) == 2 && is_numeric($urlList[1]) && $auth)
                    {
                        echo GetInfoOneTask($link, $urlList[1]);
                    }
                    else if(count($urlList) == 3 && is_numeric($urlList[1]) && $auth)
                    {
                        if($urlList[2] == "input")
                        {
                            GetInput($link, $urlList[1]);
                        }
                        else if ($urlList[2] == "output")
                        {
                            GetOutput($link, $urlList[1]);
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
                    if($user['roleId'] == 1 && count($urlList) == 1)
                    {
                        PostTask($requesData->body, $link);
                    }
                    else if($auth == 1 && is_numeric($urlList[1]) && $urlList[2] == "solution")
                    {
                        PostSolutionOnTask($requesData->body, $link, $urlList[1], $user['id']);
                    }
                    else if($user['roleId'] == 1 && is_numeric($urlList[1]))
                    {
                        if($urlList[2] == "input")
                        {
                            PostInputForTask($urlList[1], $link);
                        }
                        else if($urlList[2] == "output")
                        {
                            PostOutputForTask($urlList[1], $link);
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
                    if($user['roleId'] == 1){
                        if(count($urlList) == 2 && is_numeric($urlList[1]))
                        {
                            DeleteTask($urlList[1], $link);
                        }
                        else if($urlList[2] == "input" && is_numeric($urlList[1]))
                        {
                            DeleteTaskInput($urlList[1], $link);
                        }
                        else if($urlList[2] == "output" && is_numeric($urlList[1]))
                        {
                            DeleteTaskOutput($urlList[1], $link);
                        }
                        else
                        {
                            setHTTPStatus("404", "Unknown path");
                        }
                    }
                    break;
                }     
            case 'PATCH':
                {
                    if($user['roleId'] == 1)
                    {
                        echo PutchTask($requesData->body, $urlList[1], $link);
                    }
                    break;
                } 
            default: setHTTPStatus("400", "");
        }
    }

    function GetInfoAllTasks($link, $parameters)
    {
        $message = [];
        if(!is_null($parameters['name'])){
            $pName =  (string)$parameters['name'];
            $res = $link->query("SELECT `id`, `name`, `topicId` FROM `tasks` WHERE name = '$pName'"); 
        } 
        else if(!is_null($parameters['parent'])){
            $pTopic = $parameters['parent'];
            $res = $link->query("SELECT `id`, `name`, `topicId` FROM `tasks` WHERE topicId = $pTopic");
        } 
        else{
            $res = $link->query("SELECT `id`, `name`, `topicId` FROM `tasks`");
        }
       
        if(!$res){
            setHTTPStatus("409", "");
        }
        else{
            while($row = $res->fetch_assoc()){
                $message[] = [
                    "id" => $row['id'],
                    "name" => $row['name'],
                    "topicId" => $row['topicId'],
                ];
            }
        }
        return json_encode($message);
    }

    function GetInfoOneTask($link, $id)
    {
        $task = $link->query("SELECT `id`, `name`, `topicId`, `description`, `isDraft` FROM `tasks` WHERE id = $id")->fetch_assoc();
        $message = [];
        if(!$task){
            setHTTPStatus("409", "");
        }
        else{
            foreach ($task as $row => $value)
            {
                $message[$row] = $value;
            }
        }
        return json_encode($message);
    }

    function GetInput($link, $taskId)
    {
        $taskInput = $link->query("SELECT `input` FROM `tasks` WHERE id = '$taskId'")->fetch_assoc();

        if($taskInput)
        {
            $file = $taskInput['input'];
            readfile($file);
        }
        else
        {
            setHTTPStatus("409", $link->error);
        }
    }

    function GetOutput($link, $taskId)
    {
        $taskInput = $link->query("SELECT `output` FROM `tasks` WHERE id = '$taskId'")->fetch_assoc();

        if($taskInput)
        {
            $file = $taskInput['output'];
            readfile($file);
        }
        else
        {
            setHTTPStatus("409", $link->error);
        }
    }


    function PutchTask($body, $taskId, $link)
    {
        $message = [];
        $paramStr = "";
        $validParam = ['name', 'topicId', 'price', 'description'];
        foreach ($body as $key => $value) {
            if(!in_array($key, $validParam))
            {
                setHTTPStatus("400", "Flied '$key' doesn't exist");
            }
            
                $paramStr .= "`$key` = '$value', ";
            
        }
        $paramStr = rtrim($paramStr, ", ");
        $res = $link->query("UPDATE tasks SET $paramStr WHERE id = $taskId");
        
        if(!$res){
            setHTTPStatus("409", $link->error);
            return;
        }
        else{
            $task = $link->query("SELECT  `id`, `name`, `topicId`, `description`, `isDraft` FROM `tasks` WHERE id = '$taskId'")->fetch_assoc();
            if(!$res){
                setHTTPStatus("409", $link->error);
                return;
            }
            else{
                foreach ($task as $row => $value) {
                    $message[$row] = $value;
                }
            }
           
        }
        return json_encode($message);
    }

    function PostTask($body, $link)
    {
        $name = $body->name;
        $topicId = $body->topicId;
        $description = " \"$body->description\"";
        $price = $body->price;
        if(is_null($name) || is_null($topicId) || is_null($description) || is_null($price))
        {
            setHTTPStatus("400", "");
            exit();
        }

        $res = $link->query("INSERT INTO tasks(`name`, `topicId`, `description`, `price`) VALUES ('$name', '$topicId', $description, '$price')");
        if(!$res)
        {
            setHTTPStatus("409", $link->error);
        }
        else
        {
            $taskId = $link->insert_id;
            $task = $link->query("SELECT `name`, `topicId`, `description`, `price`, `isDraft` FROM `tasks` WHERE id = '$taskId'")->fetch_assoc();
            $message = [];
            if(!$task)
            {
                setHTTPStatus("409", $link->error);
            }
            else
            {
                foreach ($task as $row => $value) 
                {
                    $message[$row] = $value;
                }
                echo json_encode([$message]);
            }
           
        }
    }

    function PostSolutionOnTask($body, $link, $taskId, $userId)
    {
        $sourceCode = $body->sourceCode;
        $programmingLanguage = $body->programmingLanguage;
        $validLang = [ "Python", "C++", "C#", "Java"];
        if(is_null($sourceCode) || is_null($programmingLanguage) || !in_array($programmingLanguage, $validLang))
        {
            setHTTPStatus("400", "");
            exit();
        }

        $res = $link->query("INSERT INTO solutions(`sourceCode`, `programmingLanguage`, `authorId`, `taskId`) VALUES ('$sourceCode', '$programmingLanguage', '$userId','$taskId')");
        if(!$res)
        {
            setHTTPStatus("409", $link->error);
        }
        else
        {
            $solId = $link->insert_id;
            $solution = $link->query("SELECT `id`, `sourceCode`, `programmingLanguage`, `verdict`, `authorId`, `taskId`  FROM `solutions` WHERE id = '$solId'")->fetch_assoc();
            $message = [];
            if(!$solution)
            {
                setHTTPStatus("409", $link->error);
            }
            else
            {
                foreach ($solution as $row => $value) 
                {
                    $message[$row] = $value;
                }
                echo json_encode([$message]);
            }
           
        }
    }


    function PostInputForTask($taskId, $link)
    {
        $file = $_FILES['input'];
        $UploadDir = "uploads";
        if($file['type'] != "text/plain")
        {
            setHTTPStatus("403", "Wrong file type");
            exit();
        }
        $pathToUpload = $UploadDir . "/upload_" . time() . "_" . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $pathToUpload);


        $res = $link->query("UPDATE `tasks` SET `input`='$pathToUpload' WHERE id = '$taskId'");
        if(!$res)
        {
            setHTTPStatus("409", $link->error);
        } 
        else
        {
            $task = $link->query("SELECT `name`, `topicId`, `description`, `price`, `isDraft` FROM `tasks` WHERE id = '$taskId'")->fetch_assoc();
            $message = [];
            if(!$task)
            {
                setHTTPStatus("409", $link->error);
            }
            else
            {
                foreach ($task as $row => $value) 
                {
                    $message[$row] = $value;
                }
                echo json_encode($message);
            }
           
        } 
    }

    function PostOutputForTask($taskId, $link)
    {
        $file = $_FILES['output'];
        $UploadDir = "uploads";
        if($file['type'] != "text/plain")
        {
            setHTTPStatus("403", "Wrong file type");
            exit();
        }
        $pathToUpload = $UploadDir . "/upload_" . time() . "_" . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $pathToUpload);

        $res = $link->query("UPDATE `tasks` SET `output`='$pathToUpload' WHERE id = '$taskId'");
        if(!$res)
        {
            setHTTPStatus("409", $link->error);
        } 
        else
        {
            $task = $link->query("SELECT `name`, `topicId`, `description`, `price`, `isDraft` FROM `tasks` WHERE id = '$taskId'")->fetch_assoc();
            $message = [];
            if(!$task)
            {
                setHTTPStatus("409", $link->error);
            }
            else
            {
                foreach ($task as $row => $value) 
                {
                    $message[$row] = $value;
                }
                echo json_encode($message);
            }
           
        } 
    }

    function DeleteTask($taskId, $link)
    {
        $res = $link->query("DELETE FROM tasks WHERE id = $taskId");
        if(!$res){
            setHTTPStatus("409", $link->error);
        }
        else{
            setHTTPStatus("200", "OK");
        }
    }

    function DeleteTaskInput($taskId, $link)
    {
        $taskInput = $link->query("SELECT `input` FROM `tasks` WHERE id = '$taskId'")->fetch_assoc();
        if($taskInput)
        {
            $res = $link->query("UPDATE `tasks` SET `input`='' WHERE id = '$taskId'");
            if(!$res){
                setHTTPStatus("409", $link->error);
            }
            else{
                $file = $taskInput['input'];
                unlink($file);
                setHTTPStatus("200", "OK");
            }
        }
        else
        {
            setHTTPStatus("409", $link->error);
        }
    }
    function DeleteTaskOutput($taskId, $link)
    {
        $taskInput = $link->query("SELECT `output` FROM `tasks` WHERE id = '$taskId'")->fetch_assoc();
        if($taskInput)
        {
            $res = $link->query("UPDATE `tasks` SET `output`='' WHERE id = '$taskId'");
            if(!$res){
                setHTTPStatus("409", $link->error);
            }
            else{
                $file = $taskInput['output'];
                unlink($file);
                setHTTPStatus("200", "OK");
            }
        }
        else
        {
            setHTTPStatus("409", $link->error);
        }
    }



 ?>   