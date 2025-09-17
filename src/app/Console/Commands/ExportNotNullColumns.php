<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExportNotNullColumns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --table=items,categories   特定テーブルに絞り込み（カンマ区切り）
     * --schema=your_db_name      対象スキーマ（未指定なら現在接続中のDB）
     * --path=reports/notnull.csv 出力パス（storage/app 配下）
     */
    protected $signature = 'db:export-notnull
                            {--table= : Comma-separated table names to filter}
                            {--schema= : Target schema/database name}
                            {--path= : Output CSV path under storage/app (default: auto)}';

    /**
     * The console command description.
     */
    protected $description = 'Export NOT NULL columns to a CSV from INFORMATION_SCHEMA.COLUMNS (MySQL/MariaDB)';

    public function handle(): int
    {
        $schema = $this->option('schema') ?: DB::getDatabaseName();

        $tablesOpt = $this->option('table');
        $tables = null;
        if (!empty($tablesOpt)) {
            $tables = collect(explode(',', $tablesOpt))
                ->map(fn($t) => trim($t))
                ->filter()
                ->values()
                ->all();
        }

        // クエリを構築
        $sql = "
            SELECT
              TABLE_NAME,
              ORDINAL_POSITION,
              COLUMN_NAME,
              COLUMN_TYPE,
              IS_NULLABLE,
              COLUMN_DEFAULT,
              COLUMN_KEY,
              EXTRA,
              COLUMN_COMMENT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
              AND IS_NULLABLE = 'NO'
        ";

        $bindings = [$schema];

        if ($tables && count($tables)) {
            $placeholders = implode(',', array_fill(0, count($tables), '?'));
            $sql .= " AND TABLE_NAME IN ($placeholders)";
            $bindings = array_merge($bindings, $tables);
        }

        $sql .= " ORDER BY TABLE_NAME, ORDINAL_POSITION";

        $rows = DB::select($sql, $bindings);

        if (empty($rows)) {
            $this->warn('No NOT NULL columns found (check schema or table filter).');
            return Command::SUCCESS;
        }

        // 出力パスを決定
        $defaultName = 'not_null_columns_' . now()->format('Ymd_His') . '.csv';
        $relPath = $this->option('path') ?: $defaultName;   // storage/app/{relPath}
        $fullPath = storage_path('app/' . $relPath);

        // ディレクトリ作成
        @mkdir(dirname($fullPath), 0777, true);

        // CSV 書き込み
        $fp = fopen($fullPath, 'w');
        if ($fp === false) {
            $this->error('Failed to open file for writing: ' . $fullPath);
            return Command::FAILURE;
        }

        // ヘッダ
        $headers = [
            'table_name',
            'ordinal_position',
            'column_name',
            'column_type',
            'is_nullable',
            'column_default',
            'column_key',
            'extra',
            'column_comment',
        ];
        fputcsv($fp, $headers);

        foreach ($rows as $r) {
            fputcsv($fp, [
                $r->TABLE_NAME,
                $r->ORDINAL_POSITION,
                $r->COLUMN_NAME,
                $r->COLUMN_TYPE,
                $r->IS_NULLABLE,
                $r->COLUMN_DEFAULT,
                $r->COLUMN_KEY,
                $r->EXTRA,
                $r->COLUMN_COMMENT,
            ]);
        }
        fclose($fp);

        $this->info('Exported: ' . $fullPath);
        $this->line('Relative (storage/app): ' . $relPath);

        return Command::SUCCESS;
    }
}
