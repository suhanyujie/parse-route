<?php
// 示例的待解析路由内容
// 测试方法
Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
// idp项目的rpc入口接口
Router::addRoute(['POST'], '/rpc', 'App\Controller\RpcController@index');

Router::addGroup('/xbidp/', function () {
    //保存草稿
    Router::post('saveDrafts123', [Some1Controller::class, 'saveDrafts']);
    //草稿列表
    Router::post('draftsListxxx', [Some2Controller::class, 'draftsList']);
}, [
    'middleware' => [
        Auth1Middlewarexx::class,
        H123AuthMiddlewarexx::class
    ]
]);
