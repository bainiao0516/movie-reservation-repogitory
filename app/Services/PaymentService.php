<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * 決済処理のモック（常に成功として true を返す）
     * 
     * @param int $amount 請求金額
     * @param array $paymentDetails 決済情報（名前、メアド、クレカ等）
     * @return bool
     */
    public function charge(int $amount, array $paymentDetails = []): bool
    {
        // 開発用ログに記録（storage/logs/laravel.log で確認可能）
        Log::info('【モック決済成功】請求金額: ￥' . number_format($amount), [
            'customer_name' => $paymentDetails['name'] ?? 'Guest',
        ]);

        // 残高チェックや外部決済APIの代わりとして常に true を返却
        return true;
    }

    /**
     * 返金処理のモック（常に成功として true を返す）
     * 
     * @param int $amount 返金額
     * @param array $options 取引ID等の補足情報（任意）
     * @return bool
     */
    public function refund(int $amount, array $options = []): bool
    {
        // 開発用ログに記録
        Log::info('【モック返金成功】返金額: ￥' . number_format($amount), [
            'options' => $options,
        ]);

        return true;
    }
}
