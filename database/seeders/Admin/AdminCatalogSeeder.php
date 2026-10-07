<?php

namespace Database\Admin\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use VistiqCore\Services\QuestionRegistry;

class AdminCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AssessmentCatalogSeeder::class);
        $permissionCodes = ['admin.access', 'content.view', 'content.edit', 'content.review', 'content.publish', 'exams.edit', 'results.view', 'results.grade', 'reports.review', 'users.manage', 'roles.manage', 'settings.manage', 'audit.view'];
        foreach ($permissionCodes as $code) {
            DB::table('permissions')->updateOrInsert(['code' => $code], ['name' => $code]);
        }
        foreach ([
            'super-admin' => ['Super Admin', $permissionCodes],
            'content-admin' => ['Content Admin', ['admin.access', 'content.view', 'content.edit', 'exams.edit']],
            'reviewer' => ['Reviewer', ['admin.access', 'content.view', 'content.review', 'results.view', 'results.grade', 'reports.review']],
        ] as $code => [$name,$permissions]) {
            DB::table('roles')->updateOrInsert(['code' => $code], ['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            $roleId = DB::table('roles')->where('code', $code)->value('id');
            foreach ($permissions as $permission) {
                DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => DB::table('permissions')->where('code', $permission)->value('id')]);
            }
        }
        foreach (QuestionRegistry::TYPES as $code => $family) {
            DB::table('question_types')->updateOrInsert(['code' => $code], ['name' => ucwords(str_replace('_', ' ', $code)), 'supports_options' => in_array($family, ['choice', 'matching'], true)]);
        }
        $ieltsId = DB::table('exam_types')->where('code', 'ielts')->value('id');
        foreach (['listening' => 4, 'reading' => 3, 'speaking' => 3] as $skill => $parts) {
            for ($number = 1; $number <= $parts; $number++) {
                DB::table('exam_parts')->updateOrInsert(['exam_type_id' => $ieltsId, 'code' => $skill.'-part-'.$number], ['skill_id' => DB::table('skills')->where('code', $skill)->value('id'), 'name' => ucfirst($skill).' Part '.$number, 'sort_order' => $number, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        foreach (['vocabulary' => 'Vocabulary', 'collocation' => 'Collocation', 'phrasal_verb' => 'Phrasal Verb', 'idiom' => 'Idiom', 'grammar' => 'Grammar', 'question' => 'Question'] as $code => $label) {
            DB::table('learning_item_types')->updateOrInsert(['code' => $code], ['label' => $label]);
        }
        DB::table('grammar_categories')->updateOrInsert(['slug' => 'general'], ['name' => 'General Grammar', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $index => $code) {
            DB::table('proficiency_levels')->updateOrInsert(['code' => $code], ['label' => $code, 'sort_order' => $index + 1]);
        }
        foreach (['FULLSCREEN_EXIT', 'TAB_HIDDEN', 'WINDOW_BLUR', 'COPY_ATTEMPT', 'PASTE_ATTEMPT', 'CONTEXT_MENU', 'RELOAD', 'NETWORK_DISCONNECT', 'MULTIPLE_SESSION', 'TIMEOUT', 'AUTO_SUBMIT'] as $code) {
            DB::table('attempt_event_types')->updateOrInsert(['code' => $code], ['name' => $code]);
        }
        DB::table('attempt_report_reasons')->updateOrInsert(['code' => 'content_error'], ['name' => 'Lỗi nội dung', 'is_active' => true]);
        DB::table('subscription_plans')->updateOrInsert(['code' => 'free'], ['name' => 'Free', 'benefits' => json_encode([]), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }
}
