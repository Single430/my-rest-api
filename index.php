<?php

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Http\Response;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;

$loader = new Loader();
$loader->registerNamespaces(
    [
        "Store\\Toys" => __DIR__ . "/models/",
    ]
)->register();
//$loader->register();

$di = new FactoryDefault();

$di->set(
    "db",
    function (){
        $connection = new PdoMysql(array(
            "host"     => "127.0.0.1",
            "username" => "root",
            "password" => "123456",
            "dbname"   => "robotics",
            "charset"  => "utf8",   #解决中文乱码问题
        ));
        return $connection;
    }
);

$app = new Micro($di);

//接受所有的robots
$app->get(
    "/api/robots",
    function () use ($app){
        $phql = "SELECT * FROM Store\\Toys\\Robots ORDER BY id DESC ";
        $robots = $app->modelsManager->executeQuery($phql);
        $data = [];
        foreach ($robots as $robot){
            $data[] = [
                "id"    => $robot->id,
                "name"  => $robot->name,
            ];
        }
        echo print_r($data);
    }
);

//搜索名字为$name的robots(模糊匹配)
$app->get(
    "/api/robots/search/{name}",
    function ($name) use ($app){
        $phql = "SELECT * FROM Store\\Toys\\Robots WHERE name LIKE :name: ORDER BY name";

        $robots = $app->modelsManager->executeQuery(
            $phql,
            [
                "name" => "%" . $name . "%"     //(% 相当于 *: 中间包含 $name 的都搜索出来)
            ]
        );

        $data = [];

        foreach ($robots as $robot) {
            $data[] = [
                "id"   => $robot->id,
                "name" => $robot->name,
            ];
        }

        echo json_encode($data);
    }
);

//检索robots基于primary key
$app->get(
    "/api/robots/{id:[0-9]+}",
    function ($id) use ($app){
        $phql = "SELECT * FROM Store\\Toys\\Robots WHERE id = :id:";

        $robot = $app->modelsManager->executeQuery(
            $phql,
            [
                "id" => $id,
            ]
        )->getFirst();

        // Create a response
        $response = new Response();

        if ($robot === false) {
            $response->setJsonContent(
                [
                    "status" => "NOT-FOUND"
                ]
            );
        } else {
            $response->setJsonContent(
                [
                    "status" => "FOUND",
                    "data"   => [
                        "id"   => $robot->id,
                        "name" => $robot->name
                    ]
                ]
            );
        }

        return $response;
    }
);

//增加robots
$app->post(
    "/api/robots",
    function () use ($app){
        $robot = $app->request->getJsonRawBody();
        $phql = "INSERT INTO Store\\Toys\\Robots (name, type, year) VALUES (:name:, :type:, :year:)";
        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                "name" => $robot->name,
                "type" => $robot->type,
                "year" => $robot->year,
            ]
        );

        $response = new Response();
        if($status->success() === true){
            $response->setStatusCode(201, "Created");
            $robot->id = $status->getModel()->id;
            $response->setJsonContent(
                [
                    "status" => "OK",
                    "data"   => $robot,
                ]
            );
        }else{
            $response->setStatusCode(409, "Conflict");
            $error = [];
            foreach ($status->getMessages() as $message){
                $error[] = $message->getMessages();
            }
            $response->setJsonContent(
                [
                    "status"    => "ERROR",
                    "messages"  => $error,
                ]
            );
        }
        return $response;
    }
);

//升级robots基于primary key
$app->put(
    "/api/robots/{id:[0-9]+}",
    function ($id) use ($app){
        $robot = $app->request->getJsonRawBody();
        $phql = "UPDATE Store\\Toys\\Robots SET name = :name:, type = :type:, year = :year: WHERE id = :id:";
        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                "id"    => $id,
                "name"  => $robot->name,
                "type"  => $robot->type,
                "year"  => $robot->year,
            ]
        );

        $response = new Response();
        if ($status->success() === true){
            $response->setJsonContent(
                [
                    "status"    => "OK"
                ]
            );
        }else{
            $response->setStatusCode(409, "Conflict");
            $error = [];
            foreach ($status->getMessages() as $message){
                $error[] = $message->getMessage();
            }

            $response->setJsonContent(
              [
                  "statua"      => "ERROR",
                  "messages"    => $error,
              ]
            );
        }
        return $response;
    }
);

//删除robots基于primary key
$app->delete(
    "/api/robots/{id:[0-9]+}",
    function ($id) use ($app) {
        $phql = "DELETE FROM Store\\Toys\\Robots WHERE id = :id:";

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                "id" => $id,
            ]
        );

        // Create a response
        $response = new Response();

        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    "status" => "OK"
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, "Conflict");

            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    "status"   => "ERROR",
                    "messages" => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->handle();