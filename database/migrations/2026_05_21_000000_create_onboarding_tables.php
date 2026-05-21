    <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('onboarding_task_templates')) {
            Schema::create('onboarding_task_templates', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('category', 80);
                $table->string('assigned_role', 20);
                $table->unsignedInteger('sequence')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('onboarding_assignments')) {
            Schema::create('onboarding_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('employee_id');
                $table->unsignedInteger('assigned_by')->nullable();
                $table->unsignedTinyInteger('status')->default(1);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                
                $table->unique('employee_id');
                $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
                $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('onboarding_tasks')) {
            Schema::create('onboarding_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('onboarding_assignment_id')->constrained('onboarding_assignments')->cascadeOnDelete();
                $table->foreignId('onboarding_task_template_id')->nullable()->constrained('onboarding_task_templates')->nullOnDelete();
                $table->string('title');
                $table->string('category', 80);
                $table->string('assigned_role', 20);
                $table->unsignedInteger('sequence')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('completed_by')->nullable()->index();
                $table->timestamps();
            });
        }

        $templates = [
            [
                'title' => 'Sign employment contract',
                'category' => 'Documents',
                'assigned_role' => 'employee',
                'sequence' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Submit government IDs',
                'category' => 'Documents',
                'assigned_role' => 'employee',
                'sequence' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Laptop & accessories setup',
                'category' => 'IT Setup',
                'assigned_role' => 'staff',
                'sequence' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'Email & system access',
                'category' => 'IT Setup',
                'assigned_role' => 'staff',
                'sequence' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'Orientation with HR',
                'category' => 'HR',
                'assigned_role' => 'staff',
                'sequence' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'Team introduction',
                'category' => 'Training',
                'assigned_role' => 'staff',
                'sequence' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            $exists = DB::table('onboarding_task_templates')
                ->where('title', $template['title'])
                ->exists();

            if (!$exists) {
                DB::table('onboarding_task_templates')->insert([
                    ...$template,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_tasks');
        Schema::dropIfExists('onboarding_assignments');
        Schema::dropIfExists('onboarding_task_templates');
    }
};
