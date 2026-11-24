<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReserveCommand extends Command
{
    protected $signature = 'app:reserve {reservation_id}';
    protected $description = '指定した予約IDの確定処理（並行処理検証用）';

    public function handle(): int
    {
        $reservationId = $this->argument('reservation_id');

        try {
            DB::transaction(function () use ($reservationId) {
                // 悲観的ロック (lockForUpdate) を使用してレコードを取得
                // ※ ロック無しの挙動（バグ発生）を検証したい場合は ->lockForUpdate() を外します
                $reservation = Reservation::where('id', $reservationId)
                    // ->lockForUpdate()
                    ->firstOrFail();

                // すでにステータスが更新されていれば割り込み検知として例外を投げる
                if ($reservation->status !== 'temporary') {
                    throw new \Exception("二重送信検知: すでにステータスが [{$reservation->status}] です。");
                }

                // IF文通過ログ（標準出力 stdout）
                $this->info("Passed IF check! (PID: " . getmypid() . ")");

                // トランザクション処理中に割り込ませるための遅延（50ms）
                usleep(50000);

                // 本予約に更新
                $reservation->update(['status' => 'completed']);
            });

            return self::SUCCESS;

        } catch (\Throwable $e) {
            // エラーログ（標準エラー出力 stderr）
            $this->error("Failed (PID: " . getmypid() . "): " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
