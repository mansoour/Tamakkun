<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Stakeholder-defined starting structure (docs/content-sources.md) plus the
 * v0.3 permissions. Idempotent: rows are only inserted when missing, so
 * admin edits in production are never overwritten.
 *
 * Source URLs are deliberately left NULL: none has been verified yet, and
 * the project never invents provider links.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['name' => 'المعاصر', 'slug' => 'almuasir'],
            ['name' => 'المنصف', 'slug' => 'almunsif'],
            ['name' => 'المفكر', 'slug' => 'almufakkir'],
            ['name' => 'هيئة تقويم التعليم والتدريب', 'slug' => 'etec'],
        ] as $source) {
            DB::table('sources')->insertOrIgnore($source + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        $categories = [
            'quantitative' => ['الأعداد', 'الكسور', 'النسب', 'النسبة المئوية', 'النسب والتناسب', 'المتوسط', 'الأسس والجذور', 'المعادلات',
                'السرعة والزمن والمسافة', 'العمل والإنجاز', 'الاحتمالات', 'الإحصاء', 'الهندسة', 'المساحات', 'المحيط', 'الزوايا', 'المسائل اللفظية الكمية'],
            'verbal' => ['استيعاب المقروء', 'التناظر اللفظي', 'إكمال الجمل', 'الخطأ السياقي', 'الارتباط والاختلاف', 'المفردات', 'العلاقات بين الكلمات'],
        ];

        foreach ($categories as $section => $names) {
            foreach ($names as $i => $name) {
                DB::table('categories')->insertOrIgnore([
                    'section' => $section, 'name' => $name, 'slug' => $this->slug($name),
                    'sort_order' => $i, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        foreach (['الرياضيات', 'الفيزياء', 'الكيمياء', 'الأحياء'] as $i => $name) {
            DB::table('subjects')->insertOrIgnore([
                'name' => $name, 'slug' => $this->slug($name), 'sort_order' => $i,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $admin = Role::findOrCreate('admin', 'web');
        foreach (['content.manage', 'links.manage'] as $permission) {
            $admin->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('guard_name', 'web')->whereIn('name', ['content.manage', 'links.manage'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function slug(string $name): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $name), '-');
    }
};
