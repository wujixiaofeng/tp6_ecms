<?php
use think\facade\Route;
use think\facade\Db;
Route::get('/d','admin/index/d');
Route::get('/ucenter/data','admin/index/d');
Route::rule('/login','admin/index/login');
?>