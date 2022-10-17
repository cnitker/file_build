# 开发助手命令工具
> 一键生成控制器,逻辑,模型,验证层文件

## 安装方法
```
composer require cnitker/file_build --dev
```

## 使用方法

### 生成类命令

```
php think make:file-build --namespace app/admin/controller/content --model GoodsOrder

 --namespace 控制器生成目录
 --model  数据库预设模型名称
 --replace  覆盖已生成文件默认:false
```

### 配置
> 配置文件位于 config/file_build.php

```
return [
    'admin' => [
        'filename' => 'AdminBaseController',
        'namespace' => 'app\admin\controller\AdminBaseController',
    ],
    'api' => [
        'filename' => 'ApiBaseController',
        'namespace' => 'app\api\controller\ApiBaseController',
    ],
];
```
