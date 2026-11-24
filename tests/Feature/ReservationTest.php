<?php

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

uses(RefreshDatabase::class);

test('悲観的ロック：完全並行処理での二重送信阻止検証', function () {
    // 1. テストデータの生成
    $reservation = Reservation::factory()->create([
        'status' => 'temporary',
    ]);

    // 別プロセスからデータが見えるよう明示的にDBへコミット
    DB::commit();

    try {
        // 2. 並行プロセスの実行
        $pool = Process::pool(function ($pool) use ($reservation) {
            // リクエスト1（先着）
            $pool->path(base_path())
                 ->command("php artisan app:reserve {$reservation->id}");

            // 10ms待機して処理途中に割り込ませる
            usleep(10000);

            // リクエスト2（割り込み）
            $pool->path(base_path())
                 ->command("php artisan app:reserve {$reservation->id}");
        });

        $results = $pool->wait();

        // 実行結果の可視化（標準出力・標準エラー・終了コード）
        dump([
            'Process 1' => [
                'stdout'   => trim($results[0]->output()),
                'stderr'   => trim($results[0]->errorOutput()),
                'exitCode' => $results[0]->exitCode(),
            ],
            'Process 2' => [
                'stdout'   => trim($results[1]->output()),
                'stderr'   => trim($results[1]->errorOutput()),
                'exitCode' => $results[1]->exitCode(),
            ],
        ]);

        // 3. アサーション
        // 先着のプロセス1は成功 (exitCode: 0)
        expect($results[0]->successful())->toBeTrue();

        // 後着のプロセス2は排他ロック/ステータスチェックにより失敗 (exitCode: 1)
        expect($results[1]->failed())->toBeTrue();

    } finally {
        // 手動コミットしたデータを削除してクリーンアップ
        $reservation->delete();
        DB::commit();
    }
});