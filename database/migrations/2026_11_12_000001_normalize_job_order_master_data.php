<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE sales.job_orders AS jo
            SET customer_id = i.customer_id
            FROM sales.inquiries AS i
            WHERE jo.inquiry_id = i.id
              AND jo.customer_id IS NULL
              AND i.customer_id IS NOT NULL
        ");

        DB::statement("
            WITH matched_customers AS (
                SELECT jo.id, MIN(c.id) AS customer_id
                FROM sales.job_orders AS jo
                JOIN core.customers AS c
                  ON LOWER(TRIM(c.code)) = LOWER(TRIM(jo.customer_no))
                WHERE jo.customer_id IS NULL
                  AND NULLIF(TRIM(jo.customer_no), '') IS NOT NULL
                GROUP BY jo.id
                HAVING COUNT(*) = 1
            )
            UPDATE sales.job_orders AS jo
            SET customer_id = matched_customers.customer_id
            FROM matched_customers
            WHERE jo.id = matched_customers.id
        ");

        DB::statement("
            WITH matched_customers AS (
                SELECT jo.id, MIN(c.id) AS customer_id
                FROM sales.job_orders AS jo
                JOIN core.customers AS c
                  ON LOWER(TRIM(c.name)) = LOWER(TRIM(jo.customer_name))
                WHERE jo.customer_id IS NULL
                  AND NULLIF(TRIM(jo.customer_name), '') IS NOT NULL
                GROUP BY jo.id
                HAVING COUNT(*) = 1
            )
            UPDATE sales.job_orders AS jo
            SET customer_id = matched_customers.customer_id
            FROM matched_customers
            WHERE jo.id = matched_customers.id
        ");

        DB::statement("
            UPDATE sales.job_orders AS jo
            SET pic_id = i.pic
            FROM sales.inquiries AS i
            WHERE jo.inquiry_id = i.id
              AND jo.pic_id IS NULL
              AND i.pic IS NOT NULL
        ");

        DB::statement("
            WITH matched_employees AS (
                SELECT jo.id, MIN(e.id) AS employee_id
                FROM sales.job_orders AS jo
                JOIN core.employees AS e
                  ON LOWER(TRIM(e.first_name || ' ' || e.last_name)) = LOWER(TRIM(jo.pic_name))
                WHERE jo.pic_id IS NULL
                  AND NULLIF(TRIM(jo.pic_name), '') IS NOT NULL
                GROUP BY jo.id
                HAVING COUNT(*) = 1
            )
            UPDATE sales.job_orders AS jo
            SET pic_id = matched_employees.employee_id
            FROM matched_employees
            WHERE jo.id = matched_employees.id
        ");

        $unmatchedCustomer = DB::table('sales.job_orders as jo')
            ->whereNull('jo.customer_id')
            ->where(function ($query): void {
                $query->whereRaw("NULLIF(TRIM(jo.customer_no), '') IS NOT NULL")
                    ->orWhereRaw("NULLIF(TRIM(jo.customer_name), '') IS NOT NULL")
                    ->orWhereRaw("NULLIF(TRIM(jo.customer_address), '') IS NOT NULL");
            })
            ->exists();

        if ($unmatchedCustomer) {
            throw new \RuntimeException(
                'Job Order memiliki customer yang tidak dapat dipetakan secara unik ke core.customers; data belum dihapus.'
            );
        }

        $unmatchedPic = DB::table('sales.job_orders')
            ->whereNull('pic_id')
            ->whereRaw("NULLIF(TRIM(pic_name), '') IS NOT NULL")
            ->exists();

        if ($unmatchedPic) {
            throw new \RuntimeException(
                'Job Order memiliki PIC yang tidak dapat dipetakan secara unik ke core.employees; data belum dihapus.'
            );
        }

        $invalidCustomerId = DB::table('sales.job_orders as jo')
            ->leftJoin('core.customers as c', 'c.id', '=', 'jo.customer_id')
            ->whereNotNull('jo.customer_id')
            ->whereNull('c.id')
            ->exists();

        if ($invalidCustomerId) {
            throw new \RuntimeException('Job Order memiliki customer_id yang tidak ditemukan di core.customers.');
        }

        $invalidPicId = DB::table('sales.job_orders as jo')
            ->leftJoin('core.employees as e', 'e.id', '=', 'jo.pic_id')
            ->whereNotNull('jo.pic_id')
            ->whereNull('e.id')
            ->exists();

        if ($invalidPicId) {
            throw new \RuntimeException('Job Order memiliki pic_id yang tidak ditemukan di core.employees.');
        }

        Schema::table('sales.job_orders', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_no',
                'customer_name',
                'customer_address',
                'pic_name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('sales.job_orders', function (Blueprint $table): void {
            $table->string('customer_no', 100)->nullable();
            $table->string('customer_name')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('pic_name')->nullable();
        });

        DB::statement("
            UPDATE sales.job_orders AS jo
            SET customer_no = c.code,
                customer_name = c.name,
                customer_address = c.address
            FROM core.customers AS c
            WHERE c.id = jo.customer_id
        ");

        DB::statement("
            UPDATE sales.job_orders AS jo
            SET pic_name = TRIM(e.first_name || ' ' || e.last_name)
            FROM core.employees AS e
            WHERE e.id = jo.pic_id
        ");
    }
};
