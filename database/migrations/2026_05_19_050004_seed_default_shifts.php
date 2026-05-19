<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Shift;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Seeds default shift templates if they don't exist
     */
    public function up(): void
    {
        // Check if shifts table exists and seed default shifts
        if (Schema::hasTable('shifts')) {
            $defaultShifts = [
                [
                    'name' => 'Morning Shift (8-5)',
                    'shift_order' => 1,
                    'start_time' => '08:00:00',
                    'end_time' => '17:00:00',
                    'break_minutes' => 60,
                    'is_night_shift' => 0,
                    'crosses_midnight' => 0,
                    'shift_duration_minutes' => 480,
                    'days_of_week' => 'Mon-Fri',
                    'is_active' => 1,
                ],
                [
                    'name' => '8-4 (No Lunch)',
                    'shift_order' => 2,
                    'start_time' => '08:00:00',
                    'end_time' => '16:00:00',
                    'break_minutes' => 0,
                    'is_night_shift' => 0,
                    'crosses_midnight' => 0,
                    'shift_duration_minutes' => 480,
                    'days_of_week' => 'Mon-Fri',
                    'is_active' => 1,
                ],
                [
                    'name' => '9-6',
                    'shift_order' => 3,
                    'start_time' => '09:00:00',
                    'end_time' => '18:00:00',
                    'break_minutes' => 60,
                    'is_night_shift' => 0,
                    'crosses_midnight' => 0,
                    'shift_duration_minutes' => 480,
                    'days_of_week' => 'Mon-Fri',
                    'is_active' => 1,
                ],
                [
                    'name' => '10-7',
                    'shift_order' => 4,
                    'start_time' => '10:00:00',
                    'end_time' => '19:00:00',
                    'break_minutes' => 60,
                    'is_night_shift' => 0,
                    'crosses_midnight' => 0,
                    'shift_duration_minutes' => 480,
                    'days_of_week' => 'Mon-Fri',
                    'is_active' => 1,
                ],
                [
                    'name' => '11-8',
                    'shift_order' => 5,
                    'start_time' => '11:00:00',
                    'end_time' => '20:00:00',
                    'break_minutes' => 60,
                    'is_night_shift' => 0,
                    'crosses_midnight' => 0,
                    'shift_duration_minutes' => 480,
                    'days_of_week' => 'Mon-Fri',
                    'is_active' => 1,
                ],
                [
                    'name' => 'Graveyard (10pm-7am)',
                    'shift_order' => 6,
                    'start_time' => '22:00:00',
                    'end_time' => '07:00:00',
                    'break_minutes' => 60,
                    'is_night_shift' => 1,
                    'crosses_midnight' => 1,
                    'shift_duration_minutes' => 480,
                    'days_of_week' => 'Mon-Fri',
                    'is_active' => 1,
                ],
                [
                    'name' => 'Graveyard (11pm-8am)',
                    'shift_order' => 7,
                    'start_time' => '23:00:00',
                    'end_time' => '08:00:00',
                    'break_minutes' => 60,
                    'is_night_shift' => 1,
                    'crosses_midnight' => 1,
                    'shift_duration_minutes' => 480,
                    'days_of_week' => 'Mon-Fri',
                    'is_active' => 1,
                ],
            ];

            foreach ($defaultShifts as $shiftData) {
                Shift::updateOrCreate(
                    ['name' => $shiftData['name']],
                    $shiftData
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('shifts')) {
            $shiftNames = [
                'Morning Shift (8-5)',
                '8-4 (No Lunch)',
                '9-6',
                '10-7',
                '11-8',
                'Graveyard (10pm-7am)',
                'Graveyard (11pm-8am)',
            ];

            Shift::whereIn('name', $shiftNames)->delete();
        }
    }
};
