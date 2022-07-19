# A2Workspace/Laravel-Database-Patcher

一個基於專案的資料庫補丁管理工具。


## Installation | 安裝

此套件尚未發布到 **Packagist** 需透過下列方法安裝：

```
composer config repositories.laravel-database-patcher vcs https://github.com/A2Workspace/laravel-database-patcher.git
composer require "a2workspace/laravel-database-patcher:*"
```

## Usage | 如何使用

現在你可以使用 `db:patch` [Artisan 命令](https://laravel.com/docs/9.x/artisan)來管理資料庫遷移補丁。該命令將會讀取 `database/patches` 下的 [Migrations 遷移檔](https://laravel.com/docs/9.x/migrations)

```
php artisan db:patch
```
