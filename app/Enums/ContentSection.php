<?php

namespace App\Enums;

enum ContentSection: string
{
    case QUANTITATIVE = 'quantitative';
    case VERBAL = 'verbal';
    case TAHSILI = 'tahsili';

    public function label(): string
    {
        return match ($this) {
            self::QUANTITATIVE => 'القدرات الكمي',
            self::VERBAL => 'القدرات اللفظي',
            self::TAHSILI => 'التحصيلي',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::QUANTITATIVE => 'calculator',
            self::VERBAL => 'book-open',
            self::TAHSILI => 'beaker',
        };
    }

    /**
     * Quantitative and verbal content is organised by category;
     * Tahsili content by subject → chapter → topic.
     */
    public function usesCategories(): bool
    {
        return $this !== self::TAHSILI;
    }
}
