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
    public function handle(): int
    {
        $file = $this->determinePatchFile();
        if (!$file) {
            return 1;
        }

        $path = $file->getRealPath();

        if ($this->usingRevertion()) {
            return $this->callMigrateCommand($path, 'migrate:rollback');
        }

        return $this->callMigrateCommand($path);
    }

    // =========================================================================
    // = DeterminePatchFile()
    // =========================================================================

    /**
     * 決定要被使用的檔案
     *
     * @return \Symfony\Component\Finder\SplFileInfo|null
     */
    protected function determinePatchFile(): ?SplFileInfo
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
        $paths = $this->laravel['config']['database.patcher.paths'] ?? database_path('patches');

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
            ->ignoreDotFiles(true)
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

    // =========================================================================
    // = UsingRevertion()
    // =========================================================================

    /**
     * 判定是否為 revert 模式
     *
     * @return bool
     */
    protected function usingRevertion(): bool
    {
        return $this->input->hasOption('revert') && $this->option('revert');
    }

    // =========================================================================
    // = CallMigrateCommand()
    // =========================================================================

    /**
     * 呼叫運行 migrate 指令並傳入路徑
     *
     * @param  string  $path
     * @param  string  $command
     * @return int
     */
    protected function callMigrateCommand($path, string $command = 'migrate'): int
    {
        if ($this->shouldUseRealPath($path)) {
            $this->info("Running: php artisan {$command} --realpath={$path}");
            $this->call($command, ['--realpath' => $path]);
        } else {
            $path = Str::after($path, base_path());

            $this->info("Running: php artisan {$command} --path={$path}");
            $this->call($command, ['--path' => $path]);
        }

        return 0;
    }

    /**
     * 判定是否該使用完整路徑傳入
     *
     * @param  string  $path
     * @return bool
     */
    protected function shouldUseRealPath($path): bool
    {
        return ! Str::startsWith($path, base_path());
    }
}
