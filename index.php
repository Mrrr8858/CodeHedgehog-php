<?php
    include_once 'helpers/headers.php';

    header('Content-type: application/json');
   $link = mysqli_connect('127.0.0.1', "backend-1", "pass123", "backend-1");

    if(!$link){
        echo "Ошибка: Невозможно установить соединение с MySQL." . PHP_EOL;
        echo "Код ошибки errno: " . mysqli_connect_errno() . PHP_EOL;
        echo "Текст ошибки error: " . mysqli_connect_error() . PHP_EOL;
    exit;
    }
/* 
    $message = [];
    $message["users"]=[];
    $res = $link->query("SELECT id, name FROM user ORDER BY id ASC");

    if(!$res){
        echo "error";
    }
    else{
        while($row = $res->fetch_assoc()){
            $message["users"][] = [
                "id" => $row['id'],
                "login" => $row['name'],
            ];
        }
    }
 */
    function getData($method)
    {
        $data = new stdClass();
        if($method != "GET")
        {  
            $data->body =  json_decode(file_get_contents('php://input'));            
        }
        $data -> parameters = [];
        $dataGet = $_GET;
        foreach ($dataGet as $key => $value) {
            if($key != 'q')
            {
                $data -> parameters[$key] = $value;
            }
        }
        return $data;
    }

    function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    //echo json_encode(getData(getMethod()));

    $url = isset($_GET['q']) ? $_GET['q'] : '';
    $url = rtrim($url, '/');
    $urlList = explode('/', $url);

    $router = $urlList[0];
    $requesData = getData(getMethod());

    if(file_exists(realpath(dirname(__FILE__)) . '/routers/' . $router . '.php'))
    {
        include_once 'routers/' . $router . '.php';
        $method = getMethod();
        route($method, $urlList, $requesData, $link);   
    }
    else
    {
        echo "NOPE 404";
    }
?>