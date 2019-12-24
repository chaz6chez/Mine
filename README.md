# wm-api-core

***
A simple API framework for WorkerMan

***

## 更新
**2019-06-24**
1) 新增Response类、Env类；
2) Tool类新增一些启动器的辅助方法函数；
3) 调整Service类与Instance类中部分方法；
4) 修复Connection类中可能导致数据库操作异常的BUG；
5) 

**2019-02-11**
1) 修复框架BUG：数据库驱动组件、缓存、CoreServer等BUG；
2) 调整MQ消费者消费方式，使用定时器配合非阻塞消费方法改进；
3) 新增Permanent常驻单例容器组件；
4) 框架优化调整；

**2018-12-05**
1) 调整框架加载内容方法；
2) 使用CoreServer替代HttpServer，提升性能；
3) 修复框架加载BUG可能导致内存溢出问题；

## 目录结构
```
|-- api                                 // 项目目录
    |-- Common                          // 公共目录
        |-- Service                     // 公共服务
        |-- configs.php                 // 公共配置文件
        |-- functions.php               // 公共方法库
    |-- V1                              // 版本目录V1
        |-- Controller                  // 控制器
        |-- Service                     // 服务
        |-- Msg                         // 错误提示语
        |-- Struct                      // 结构器(验证器)
        |-- config.php                  // 配置内容
    |-- V2                              // 版本目录V2
    ...
|-- public                              // 静态页面及资源
|-- server                              // 系统入口
    |-- log                             // 日志目录
    |-- launcher                        // 启动器目录
        |-- app                         // App启动器目录
            |-- launcher_app_server.php
        |-- admin                       // Admin启动器目录
    |-- .env                            // 环境配置
    |-- launcher.php                    // 全局启动器
|-- vendor                              // composer组件
    |-- chaz6chez
        |-- wm-api-frame                // 框架核心
            |-- examples
            |-- lib
                |-- Base
                |-- Core
                |-- Cron
                |-- Db
                |-- Definition
                |-- Helper
                |-- Queue
                |-- App.php
```
