# BobMigrate
#### Bob数据库迁移工具

1. 把`BobMigrate`文件夹直接复制到 `public` 目录下面

2. 把 `BobMigrateController.php` 文件复制到 `Controllers` 目录下面

3. 在`routes.php` 文件里面添加以下代码
```php
$app->post('/api/migration', App\Controllers\BobMigrateController::class . ':migration');
$app->post('/api/authAdmin', App\Controllers\BobMigrateController::class . ':authAdmin');
```

4. 访问地址
`http://xxxx/BobMigrate/index.html`

### 联系方式

联系我：[@Bobs9](https://t.me/Bobs9)
