<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InquiryCodeService
{
    private const SALES_ROLE_ID = 2;

    /**
     * Reserve a code while the caller's transaction holds the sales-role row lock.
     */
    public function reserve(int $picId, string $inquiryDate): string
    {
        $salesRole = DB::table('sales.user_role')
            ->where('employee_id', $picId)
            ->where('role_id', self::SALES_ROLE_ID)
            ->lockForUpdate()
            ->first(['sales_code']);

        $salesCode = $salesRole?->sales_code;

        if (! is_string($salesCode) || preg_match('/^\d{2}$/D', $salesCode) !== 1) {
            throw ValidationException::withMessages([
                'pic' => 'PIC Sales harus memiliki kode Sales dua digit di Role Management.',
            ]);
        }

        $date = Carbon::parse($inquiryDate);
        $counter = DB::table('sales.inquiry_code_counters')
            ->where('pic_id', $picId)
            ->where('inquiry_date', $date->toDateString())
            ->lockForUpdate()
            ->first();

        if ($counter === null) {
            $nextSequence = $this->nextSequence($picId, $date, $salesCode);

            DB::table('sales.inquiry_code_counters')->insert([
                'pic_id' => $picId,
                'inquiry_date' => $date->toDateString(),
                'sales_code' => $salesCode,
                'next_sequence' => $nextSequence,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DB::table('sales.inquiry_code_counters')
                ->where('pic_id', $picId)
                ->where('inquiry_date', $date->toDateString())
                ->lockForUpdate()
                ->first();
        }

        if ($counter === null) {
            throw new \RuntimeException('Counter kode Inquiry gagal dibuat.');
        }

        if (filled($counter->reserved_code)) {
            return $counter->reserved_code;
        }

        $sequence = (int) $counter->next_sequence;
        $code = $this->formatCode($date, $salesCode, $sequence);

        DB::table('sales.inquiry_code_counters')
            ->where('id', $counter->id)
            ->update([
                'sales_code' => $salesCode,
                'next_sequence' => $sequence + 1,
                'reserved_sequence' => $sequence,
                'reserved_code' => $code,
                'updated_at' => now(),
            ]);

        return $code;
    }

    public function consume(int $picId, string $inquiryDate, string $code): void
    {
        $counter = DB::table('sales.inquiry_code_counters')
            ->where('pic_id', $picId)
            ->where('inquiry_date', Carbon::parse($inquiryDate)->toDateString())
            ->lockForUpdate()
            ->first();

        if (
            $counter === null
            || ! is_string($counter->reserved_code)
            || ! hash_equals($counter->reserved_code, $code)
        ) {
            throw ValidationException::withMessages([
                'code' => 'Kode Inquiry tidak lagi aktif. Muat ulang kode sebelum menyimpan.',
            ]);
        }

        DB::table('sales.inquiry_code_counters')
            ->where('id', $counter->id)
            ->update([
                'reserved_sequence' => null,
                'reserved_code' => null,
                'updated_at' => now(),
            ]);
    }

    private function nextSequence(int $picId, Carbon $date, string $salesCode): int
    {
        $pattern = sprintf(
            '/^PN\/SSN\/%s\.%s\.(\d+)\/%s\/%s$/D',
            $date->format('dm'),
            preg_quote($salesCode, '/'),
            $this->romanMonth($date),
            $date->format('y')
        );
        $maxSequence = 0;

        $codes = DB::table('sales.inquiries')
            ->where('pic', $picId)
            ->whereDate('date', $date->toDateString())
            ->pluck('code');

        foreach ($codes as $existingCode) {
            if (preg_match($pattern, $existingCode, $matches) === 1) {
                $maxSequence = max($maxSequence, (int) $matches[1]);
            }
        }

        return $maxSequence + 1;
    }

    private function formatCode(Carbon $date, string $salesCode, int $sequence): string
    {
        $sequenceCode = str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);

        return sprintf(
            'PN/SSN/%s.%s.%s/%s/%s',
            $date->format('dm'),
            $salesCode,
            $sequenceCode,
            $this->romanMonth($date),
            $date->format('y')
        );
    }

    private function romanMonth(Carbon $date): string
    {
        return [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ][$date->month];
    }
}
