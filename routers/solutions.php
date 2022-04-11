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
            $auth = 1;
        }
        else
        {
            $auth = 0;
        }

        switch($method){
            case 'GET':
                {
                    if(count($urlList) == 1)
                    {
                       echo GetAllSolutions($link);
                    }
                    else
                    {
                        setHTTPStatus("404", "Unknown path");
                    }
                    break;
                }
            case 'POST':
                {
                    if(count($urlList) == 3 && $user['roleId'] == 1 && is_numeric($urlList[1]) && $urlList[2] == "postmoderation")
                    {
                       echo PostVerdict($requesData->body, $link, $urlList[1]);
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

    function GetAllSolutions($link)
    {
        $solution = $link->query("SELECT `id`, `sourceCode`, `programmingLanguage`, `verdict`, `authorId`, `taskId`  FROM `solutions`");
        $message = [];
        if(!$solution)
        {
            setHTTPStatus("409", $link->error);
        }
        else
        {
            while($row = $solution->fetch_assoc()){
                $message[] = [
                    "id" => $row['id'],
                    "sourceCode" => $row['sourceCode'],
                    "programmingLanguage" => $row['programmingLanguage'],
                    "verdict" => $row['verdict'],
                    "authorId" => $row['authorId'],
                    "taskId" => $row['taskId'],

                ];
            }
            echo json_encode($message);
        }
    }

    function PostVerdict($body, $link, $solId)
    {
        $verdict = $body->verdict;
        $validVerdict = ["Pending", "OK", "Rejected"];
        if(is_null($verdict) || !in_array($verdict, $validVerdict))
        {
            setHTTPStatus("400", "");
            exit();
        }

        echo $taskId;

        $res = $link->query("UPDATE `solutions` SET `verdict`='$verdict' WHERE id = '$solId'");
        if(!$res)
        {
            setHTTPStatus("409", $link->error);
        }
        else
        {
            $taskId = $link->query("SELECT `taskId`  FROM `solutions` WHERE id = '$solId'")->fetch_assoc();
            $taskId = $taskId['taskId'];

            $task = $link->query("SELECT `id`, `name`, `topicId`, `description`, `price`, `isDraft` FROM `tasks` WHERE id = '$taskId'")->fetch_assoc();
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

 ?>   