<?php

namespace A2Workspace\DatabasePatcher\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class DbPatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:patch {filter? : 指定或搜尋檔案}
                    {--r|revert : Revert the patch file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '執行資料庫補丁檔';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * 要排除的檔案
     *
     * @var string[]
     */
    protected array $excludedNames = [
        'README.md',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $file = $this->determinePatchFile();
        if (!$file) {
            return 1;
        }

        $path = $file->getRealPath();
        $path = Str::after($path, base_path());

        // 這邊我們判斷
        // 若為回復模式則呼叫滾回命令並傳入補丁檔案路徑
        if ($this->option('revert')) {
            $this->info("Running: php artisan migrate:rollback --path={$path}");

            $this->call('migrate:rollback', [
                '--path' => $path,
            ]);
        }

        // 呼叫遷移命令並傳入補丁檔案路徑
        else {
            $this->info("Running: php artisan migrate --path={$path}");

            $this->call('migrate', [
                '--path' => $path,
            ]);
        }

        return 0;
    }

    /**
     * 決定要被使用的檔案
     *
     * @return \Symfony\Component\Finder\SplFileInfo|null
     */
    protected function determinePatchFile()
    {
        // 取得 patches 目錄的檔案列表，若結果為空則提前終止
        $files = $this->getFileList();
        if ($files->isEmpty()) {
            $this->warn('找不到任何補丁檔案');

            return null;
        }

        // 這邊處理有輸入 filter 參數的場合。
        if ($inputFilter = $this->getFilterInput()) {
            $filtered = $files->filter(function (SplFileInfo $file) use ($inputFilter) {
                return Str::contains($file->getRelativePathname(), $inputFilter);
            });

            if ($filtered->isEmpty()) {
                $this->error('找不到符合的補丁檔案');

                return null;
            }

            $files = $filtered;
        }

        return $this->choiceFromFileList('選擇補丁檔案', $files);
    }

    /**
     * @return string
     */
    protected function getFilterInput()
    {
        return trim($this->argument('filter'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Symfony\Component\Finder\SplFileInfo>
     */
    protected function getFileList(): Collection
    {
        $paths = [database_path('patches')];

        return collect($paths)
            ->map(fn ($path) => $this->getFileListInDirectory($path))
            ->collapse();
    }

    /**
     * 回傳指令目錄下的檔案。排除目錄的與 $excludedNames 指定的檔案。
     *
     * @param  string  $path
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    protected function getFileListInDirectory(string $path): array
    {
        $finder = Finder::create()
            ->filter(function (SplFileInfo $file) {
                return !in_array($file->getRelativePathname(), $this->excludedNames);
            })
            ->files()
            ->in($path)
            ->depth(0)
            ->sortByName();

        return iterator_to_array($finder, false);
    }

    /**
     * 讓使用者自檔案列表中選取一個。
     *
     * @param  string  $question
     * @param  \Illuminate\Support\Collection  $files
     * @return \Symfony\Component\Finder\SplFileInfo
     */
    protected function choiceFromFileList($question, Collection $files): SplFileInfo
    {
        $formattedFiles = $files->map(function (SplFileInfo $file) {
            $label = $file->getRelativePathname();

            // 加上符號隔開避免 choice 時索引與檔案名稱混淆 (這應該是 Symfony 的 bug 待查證)
            $label = "-> {$label}";

            return [$label, $file];
        });

        $options = $formattedFiles->pluck(0)->toArray();

        $input = $this->choice($question, $options);

        return $formattedFiles->first(function ($value) use ($input) {
            return $value[0] === $input;
        })[1];
    }
}
