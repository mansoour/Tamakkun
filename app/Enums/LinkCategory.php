<?php

namespace App\Enums;

enum LinkCategory: string
{
    case QIYAS = 'qiyas';
    case QUDURAT = 'qudurat';
    case TAHSILI = 'tahsili';
    case OFFICIAL_SERVICES = 'official_services';
    case LEARNING_RESOURCES = 'learning_resources';

    public function label(): string
    {
        return match ($this) {
            self::QIYAS => 'قياس',
            self::QUDURAT => 'القدرات',
            self::TAHSILI => 'التحصيلي',
            self::OFFICIAL_SERVICES => 'خدمات رسمية',
            self::LEARNING_RESOURCES => 'مصادر تعليمية',
        };
    }
}
