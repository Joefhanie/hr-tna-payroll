<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendance')) {
            return;
        }

        if (Schema::hasTable('attendance_legacy')) {
            $this->finalizeLegacyConversion();
            return;
        }

        if (
            Schema::hasColumn('attendance', 'emp_id')
            && Schema::hasColumn('attendance', 'punch_type')
            && Schema::hasColumn('attendance', 'time')
            && !Schema::hasColumn('attendance', 'user_id')
            && !Schema::hasColumn('attendance', 'check_in')
            && !Schema::hasColumn('attendance', 'check_out')
        ) {
            $this->normalizeConvertedTable();
            return;
        }

        if (!Schema::hasColumn('attendance', 'user_id')) {
            return;
        }

        DB::statement('RENAME TABLE `attendance` TO `attendance_legacy`');

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emp_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->string('punch_type', 10);
            $table->date('attendance_date');
            $table->time('time')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Present, 2=Late, 3=Absent, 4=Excused');
            $table->timestamps();

            $table->index(['emp_id', 'attendance_date'], 'idx_attendance_emp_date');
        });

                DB::statement(<<<'SQL'
                        INSERT INTO `attendance` (`emp_id`, `shift_id`, `punch_type`, `attendance_date`, `time`, `status`, `created_at`, `updated_at`)
                        SELECT u.`employee_id`, a.`shift_id`, 'in', a.`attendance_date`, TIME(a.`check_in`), a.`status`, a.`created_at`, a.`updated_at`
                        FROM `attendance_legacy` a
                        INNER JOIN `users` u ON u.`id` = a.`user_id`
                        WHERE a.`check_in` IS NOT NULL
                            AND u.`employee_id` IS NOT NULL
                SQL);

                DB::statement(<<<'SQL'
                        INSERT INTO `attendance` (`emp_id`, `shift_id`, `punch_type`, `attendance_date`, `time`, `status`, `created_at`, `updated_at`)
                        SELECT u.`employee_id`, a.`shift_id`, 'out', a.`attendance_date`, TIME(a.`check_out`), a.`status`, a.`created_at`, a.`updated_at`
                        FROM `attendance_legacy` a
                        INNER JOIN `users` u ON u.`id` = a.`user_id`
                        WHERE a.`check_out` IS NOT NULL
                            AND u.`employee_id` IS NOT NULL
                SQL);

        DB::statement('DROP TABLE `attendance_legacy`');
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance')) {
            return;
        }

        if (
            Schema::hasColumn('attendance', 'user_id')
            && Schema::hasColumn('attendance', 'check_in')
            && Schema::hasColumn('attendance', 'check_out')
        ) {
            return;
        }

        if (!Schema::hasColumn('attendance', 'emp_id')) {
            return;
        }

        DB::statement('RENAME TABLE `attendance` TO `attendance_punches`');

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->date('attendance_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Present, 2=Late, 3=Absent, 4=Excused');
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date']);
        });

        DB::statement(<<<'SQL'
            INSERT INTO `attendance` (`user_id`, `shift_id`, `attendance_date`, `check_in`, `check_out`, `status`, `created_at`, `updated_at`)
            SELECT u.`id`, a.`shift_id`, a.`attendance_date`,
                MAX(CASE WHEN a.`punch_type` = 'in' THEN a.`time` END),
                MAX(CASE WHEN a.`punch_type` = 'out' THEN a.`time` END),
                MAX(a.`status`),
                MIN(a.`created_at`),
                MAX(a.`updated_at`)
            FROM `attendance_punches` a
            INNER JOIN `employees` e ON e.`id` = a.`emp_id`
            INNER JOIN `users` u ON u.`employee_id` = e.`id`
            GROUP BY u.`id`, a.`shift_id`, a.`attendance_date`
        SQL);

        DB::statement('DROP TABLE `attendance_punches`');
    }

    private function finalizeLegacyConversion(): void
    {
        DB::statement('DELETE FROM `attendance`');

        $this->normalizeConvertedTable();

        DB::statement(<<<'SQL'
            INSERT INTO `attendance` (`emp_id`, `shift_id`, `punch_type`, `attendance_date`, `time`, `status`, `created_at`, `updated_at`)
            SELECT u.`employee_id`, a.`shift_id`, 'in', a.`attendance_date`, TIME(a.`check_in`), a.`status`, a.`created_at`, a.`updated_at`
            FROM `attendance_legacy` a
            INNER JOIN `users` u ON u.`id` = a.`user_id`
            WHERE a.`check_in` IS NOT NULL
              AND u.`employee_id` IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO `attendance` (`emp_id`, `shift_id`, `punch_type`, `attendance_date`, `time`, `status`, `created_at`, `updated_at`)
            SELECT u.`employee_id`, a.`shift_id`, 'out', a.`attendance_date`, TIME(a.`check_out`), a.`status`, a.`created_at`, a.`updated_at`
            FROM `attendance_legacy` a
            INNER JOIN `users` u ON u.`id` = a.`user_id`
            WHERE a.`check_out` IS NOT NULL
              AND u.`employee_id` IS NOT NULL
        SQL);

        DB::statement('DROP TABLE `attendance_legacy`');
    }

    private function normalizeConvertedTable(): void
    {
        $employeeKeyType = Schema::getColumnType('employees', 'id');
        $shiftKeyType = Schema::getColumnType('shifts', 'id');

        if (Schema::hasColumn('attendance', 'time')) {
            DB::statement('ALTER TABLE `attendance` MODIFY `time` TIME NULL');
        }

        if ($this->isLegacyIntegerKeyType($employeeKeyType)) {
            DB::statement('ALTER TABLE `attendance` MODIFY `emp_id` INT UNSIGNED NOT NULL');
        }

        if ($this->isLegacyIntegerKeyType($shiftKeyType)) {
            DB::statement('ALTER TABLE `attendance` MODIFY `shift_id` INT UNSIGNED NULL');
        }

        if (!Schema::hasColumn('attendance', 'emp_id')) {
            return;
        }

        if (! $this->foreignKeyExists('attendance', 'attendance_emp_id_foreign')) {
            DB::statement(<<<'SQL'
                ALTER TABLE `attendance`
                ADD CONSTRAINT `attendance_emp_id_foreign`
                FOREIGN KEY (`emp_id`) REFERENCES `employees` (`id`)
                ON DELETE CASCADE ON UPDATE CASCADE
            SQL);
        }

        if (! $this->foreignKeyExists('attendance', 'attendance_shift_id_fk')) {
            DB::statement(<<<'SQL'
                ALTER TABLE `attendance`
                ADD CONSTRAINT `attendance_shift_id_fk`
                FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`)
                ON DELETE SET NULL ON UPDATE CASCADE
            SQL);
        }
    }

    private function isLegacyIntegerKeyType(?string $type): bool
    {
        return in_array(strtolower((string) $type), ['int', 'integer'], true);
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $result = DB::selectOne(<<<'SQL'
            SELECT COUNT(*) AS total
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        SQL, [$table, $constraint]);

        return (int) ($result->total ?? 0) > 0;
    }
};
