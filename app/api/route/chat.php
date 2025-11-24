<?php

use think\facade\Route;
use app\api\middleware\IsLogin;

Route::group('View/Chat', function(){
    Route::get('room', '/api/View/chatRoom', 'GET');
});

Route::group('Chat', function(){
    Route::rule('record', '/api/Chat/record', 'POST');
});