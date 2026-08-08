<?php

namespace Tests\Unit\MobileApi;

use App\MobileApi\V1\Services\DateRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class DateRangeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_today_range_and_previous_period_are_calculated(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-31 14:00:00', 'Europe/Paris'));
        $request = Request::create('/api/mobile/v1/dashboard', 'GET', ['range' => 'today']);

        $range = (new DateRange())->fromRequest($request);

        $this->assertSame('today', $range['key']);
        $this->assertSame('2026-07-31 00:00:00', $range['from']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-31 23:59:59', $range['to']->format('Y-m-d H:i:s'));
        $this->assertTrue($range['previous_to']->lt($range['from']));
    }

    public function test_custom_range_uses_complete_days(): void
    {
        $request = Request::create('/api/mobile/v1/dashboard', 'GET', [
            'range' => 'custom',
            'from' => '2026-07-01',
            'to' => '2026-07-15',
        ]);

        $range = (new DateRange())->fromRequest($request);

        $this->assertSame('2026-07-01 00:00:00', $range['from']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-15 23:59:59', $range['to']->format('Y-m-d H:i:s'));
    }
}
