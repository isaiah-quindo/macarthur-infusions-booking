<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Encrypted ciphertext (base64-wrapped) is much longer than the
        // original plaintext, so widen the PII columns to TEXT before we
        // try to write encrypted values into them. The customer_email
        // index becomes useless once values are encrypted — drop it.
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['customer_email']);
        });

        DB::statement('ALTER TABLE bookings ALTER COLUMN customer_name TYPE text');
        DB::statement('ALTER TABLE bookings ALTER COLUMN customer_email TYPE text');
        DB::statement('ALTER TABLE bookings ALTER COLUMN customer_phone TYPE text');

        // Backfill: encrypt existing plaintext rows in place. Done in one
        // transaction so a half-encrypted table is impossible. The model
        // is NOT used here — Crypt::encryptString matches Laravel's
        // `encrypted` cast format exactly.
        DB::transaction(function () {
            DB::table('bookings')
                ->select('id', 'customer_name', 'customer_email', 'customer_phone', 'notes')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('bookings')->where('id', $row->id)->update([
                            'customer_name' => Crypt::encryptString($row->customer_name),
                            'customer_email' => Crypt::encryptString($row->customer_email),
                            'customer_phone' => Crypt::encryptString($row->customer_phone),
                            'notes' => $row->notes === null ? null : Crypt::encryptString($row->notes),
                        ]);
                    }
                });
        });
    }

    public function down(): void
    {
        // Decrypt back to plaintext, then narrow the columns and restore the
        // email index. If a row's value isn't valid ciphertext (e.g. created
        // before the up() ran), leave it as-is.
        DB::transaction(function () {
            DB::table('bookings')
                ->select('id', 'customer_name', 'customer_email', 'customer_phone', 'notes')
                ->orderBy('id')
                ->chunkById(500, function ($rows) {
                    foreach ($rows as $row) {
                        DB::table('bookings')->where('id', $row->id)->update([
                            'customer_name' => $this->tryDecrypt($row->customer_name),
                            'customer_email' => $this->tryDecrypt($row->customer_email),
                            'customer_phone' => $this->tryDecrypt($row->customer_phone),
                            'notes' => $row->notes === null ? null : $this->tryDecrypt($row->notes),
                        ]);
                    }
                });
        });

        DB::statement('ALTER TABLE bookings ALTER COLUMN customer_name TYPE varchar(255)');
        DB::statement('ALTER TABLE bookings ALTER COLUMN customer_email TYPE varchar(255)');
        DB::statement('ALTER TABLE bookings ALTER COLUMN customer_phone TYPE varchar(255)');

        Schema::table('bookings', function (Blueprint $table) {
            $table->index('customer_email');
        });
    }

    private function tryDecrypt(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }
};
