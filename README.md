# wm-api-core

***
A simple API framework for WorkerMan

**wm-api核心部分**
***

## 更新
**2019-02-11**
1) 修复框架BUG：数据库驱动组件、缓存、CoreServer等BUG；
2) 调整MQ消费者消费方式，使用定时器配合非阻塞消费方法改进；
3) 新增Permanent常驻单例容器组件；
4) 框架优化调整；

**2018-12-05**
1) 调整框架加载内容方法；
2) 使用CoreServer替代HttpServer，提升性能；
3) 修复框架加载BUG可能导致内存溢出问题；

## 简单介绍
1) 以**WorkerMan**为核心开发的**HttpServer**。
2) 提供轻量级Route及加载方式。
3) 提供享元模式的单例容器管理实例，内置GC方案：
    - **Model类**：负责管理模型类单例
    - **Instance类**：负责管理其他单例
4) 提供**RabbitMQ**的生产者服务与消费者组件。
5) 框架提供**Redis**、**Mysql类**，并对常驻内存进行了适配和调整。
6) 框架提供轻量级验证器**structure**。
7) 框架提供内置组件（**Service类**）及统一输入输出方式（**Output类**、**Result类**）。
8) 框架提供助手类：
    - **Apcu类**：基于Apcu的进程间通讯助手
    - **Template类**：模板工具助手
    - **Arr类**：数组助手
    - **Language类**：语言包输出助手
9) 框架使用**Composer PSR-4**自动加载，灵活性高。


## 支持

|拓展名|说明|
|:---:|:---|
|PDO|用于Mysql的支撑|
|amqp|用于RabbitMQ的支撑|
|redis|用于Redis的支撑|
|grpc|用于gRPC的支撑|
|protobuf|用于提高Linux下gRPC数据转换的效率，win下使用protobuf库|
|sockets|用于基础服务的支撑|
|openssl|用于基础服务的支撑|
|event|用于提高workerman在Linux下的处理能力
|apcu|用于用户进程间数据共享|

