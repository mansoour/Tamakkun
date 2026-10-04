<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * «تجميعات المنصف 1500 سؤال»: the free PDF edition (its Drive title reads
 * «المنصف 1500 (مجاني)»), at the top of القدرات الكمي ← مراجع وتجميعات, with
 * the seeded source المنصف. Inserted only when its slug is missing.
 */
return new class extends Migration
{
    private const SLUG = 'almunsif-1500';

    public function up(): void
    {
        if (! app()->runningUnitTests()) {
            $this->importContent();
        }
    }

    public function importContent(): void
    {
        $categoryId = DB::table('categories')->where(['section' => 'quantitative', 'slug' => 'مراجع-وتجميعات'])->value('id');

        if ($categoryId === null || DB::table('contents')->where('slug', self::SLUG)->exists()) {
            return;
        }

        $now = now();
        DB::table('contents')->insert([
            'title' => 'تجميعات المنصف 1500 سؤال',
            'slug' => self::SLUG,
            'description' => 'السلاح السري للتدريب! لا يمكن الاستغناء عن حل تجميعات قدرات التي تمنحك تصورًا حقيقيًا عن أسئلة الاختبار وتزيد من سرعتك وثقتك. ملف PDF مجاني على Google Drive.',
            'content_type' => 'link',
            'section' => 'quantitative',
            'category_id' => $categoryId,
            'source_id' => DB::table('sources')->where('slug', 'almunsif')->value('id'),
            'stage' => 'review',
            'external_url' => 'https://drive.google.com/file/d/1ispc42x082FCEBm0Dn2diJ5qx9sKMpFK/view',
            'sort_order' => 0,
            'is_published' => true,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('contents')->where('slug', self::SLUG)->delete();
    }
};
