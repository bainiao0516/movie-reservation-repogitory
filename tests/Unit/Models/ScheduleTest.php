<?php

namespace Tests\Unit\Models;

use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase; // DBを使う場合は Framework\TestCase ではなく Tests\TestCase をインポート

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    // isBookableのテスト
    public function test_is_bookable_returns_true_when_schedule_disabled(): void
    {
        $schedule = Schedule::factory()->create([
            'start_at' => now()->subMinutes(16),
        ]);

        $this->assertFalse($schedule->isBookable());
    }
}
