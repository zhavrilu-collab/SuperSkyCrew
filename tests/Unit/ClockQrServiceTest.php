<?php

namespace Tests\Unit;

use App\Services\ClockQrService;
use PHPUnit\Framework\TestCase;

class ClockQrServiceTest extends TestCase
{
    public function test_parse_accepts_payload_raw_token_and_query(): void
    {
        $qr = new ClockQrService;

        $this->assertSame('abcdef0123456789', $qr->parse('hr1:demo-tvrtka:abcdef0123456789', 'demo-tvrtka'));
        $this->assertSame('abcdef0123456789', $qr->parse('ABCDEF0123456789', 'demo-tvrtka'));
        $this->assertSame('abcdef0123456789', $qr->parse('https://hr.test/x/kiosk/t?qr=abcdef0123456789', 'demo-tvrtka'));
        $this->assertNull($qr->parse('hr1:druga:abcdef0123456789', 'demo-tvrtka'));
        $this->assertNull($qr->parse('nije-qr', 'demo-tvrtka'));
    }
}
