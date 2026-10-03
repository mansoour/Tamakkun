<?php

namespace App\Enums;

enum ContentType: string
{
    case VIDEO = 'video';
    case LESSON = 'lesson';
    case LINK = 'link';
    case ARTICLE = 'article';
    case PRACTICE = 'practice';
    case QUIZ = 'quiz';

    public function label(): string
    {
        return match ($this) {
            self::VIDEO => 'مقطع فيديو',
            self::LESSON => 'درس',
            self::LINK => 'رابط خارجي',
            self::ARTICLE => 'مقال',
            self::PRACTICE => 'تدريب',
            self::QUIZ => 'اختبار قصير',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::VIDEO => 'play-circle',
            self::LESSON => 'academic-cap',
            self::LINK => 'link',
            self::ARTICLE => 'document-text',
            self::PRACTICE => 'puzzle-piece',
            self::QUIZ => 'clipboard-document-list',
        };
    }
}
