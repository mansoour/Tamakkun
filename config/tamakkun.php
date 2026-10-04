<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default platform settings
    |--------------------------------------------------------------------------
    |
    | Fallback values for App\Services\SettingsService. Administrators
    | override them in the `settings` table; a stored value always wins.
    | Only keys listed here can be written, which protects against typos.
    |
    */

    'settings' => [
        'platform_name' => 'تمكّن',
        'tagline' => 'خطوتك اليوم… تصنع نتيجتك غدًا',
        'default_target_score' => 85,
        'inactivity_days' => 7,
        'upcoming_exam_alert_days' => 14,
        'low_activity_threshold' => 2,
        'enable_gamification' => true,
        'enable_guardian_accounts' => false,
        'force_password_change' => true,
        'weekly_content_goal' => 6,
        'email_notifications' => true,
        'support_email' => null,
        'support_phone' => null,
        'privacy_url' => null,
        'terms_url' => null,
        'about_text' => null,
        'privacy_text' => null,
        'terms_text' => null,
        // Who runs the platform; shown on the home page, login page and footer.
        'supervisor_name' => 'أ. فاطمة الشهراني',
        'supervisor_title' => 'إشراف وإدارة المنصة',
        // Student self-registration: open (active at once), approval (an admin activates) or closed.
        'student_registration' => 'open',
    ],

];
