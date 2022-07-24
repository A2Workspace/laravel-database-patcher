# A2Workspace/Laravel-Database-Patcher

<p align="center"><img src="/.github/animation.gif" alt="Laravel-Database-Patcher demo animation"></p>

一個基於專案的資料庫補丁管理工具。

隨著專案的進行，難免需要對現有的資料表修修補補，但檔案過於複雜的 `database/migrations` 可能會產生一些問題。`a2workspace/laravel-database-patcher` 提供簡易的管理工具，讓你可以使用獨立的 `database/patches` 資料夾，來放置額外的 **遷移檔 (Migration)** 。

本套件可以解決以下問題:
- 開發者想增加某些修復用的 **遷移檔 (Migration)** ，但又不想放進 `database/migrations` 影響開發或測試的情形
- 運維人員想編寫一些資料修正的腳本，又不想影響開發或測試的情形
- 運維人員想編寫一些資料修正的腳本，並在測試機環境執行一遍，待測試過了才在正式機環境運行的情形

## Installation | 安裝

執行下列命令透過 **composer** 引入到你的 **Laravel** 專案:

```
composer require a2workspace/laravel-database-patcher
```

接著使用 `vendor:publish` 命令生成 `database/patches` 資料夾:

```bash
php artisan vendor:publish --tag=@a2workspace/laravel-database-patcher
```

## Usage | 如何使用

現在你可以使用 `db:patch` [Artisan 命令](https://laravel.com/docs/9.x/artisan)來管理資料庫遷移補丁。

```bash
php artisan db:patch
```

該命令將會讀取 `database/patches` 下的 [Migrations 遷移檔](https://laravel.com/docs/9.x/migrations)，並列出可用選項。

### Reverting Back Patches | 還原已安裝的補丁
使用 `--revert` 或簡寫 `-r` 參數，可將已安裝的遷移檔進行 **滾回 (rollback)** 動作:

```bash
php artisan db:patch --revert
```

## Generating Patches | 如何產生補丁檔

補丁與原生的 **遷移檔 (Migration)** 完全相同，你可以參考 [Generating Migrations](https://laravel.com/docs/9.x/migrations#generating-migrations) 與 [Updating Tables](https://laravel.com/docs/9.x/migrations#updating-tables) 的說明生成 **遷移檔 (Migration)** ，接著再把檔案移至 `database/patches` 資料夾內。

或直接用 `make` 命令生成:

```bash
php artisan make:migration --path=database/patches add_soft_deletes_to_users_table
```
