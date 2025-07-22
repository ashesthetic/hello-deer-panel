<?php

namespace App\Utils;

use Carbon\Carbon;

class TimezoneUtil
{
    /**
     * Get the application timezone
     */
    public static function getTimezone(): string
    {
        return config('app.timezone', 'America/Edmonton');
    }

    /**
     * Create a Carbon instance in the application timezone
     */
    public static function now(): Carbon
    {
        return Carbon::now()->setTimezone(self::getTimezone());
    }

    /**
     * Parse a date string in the application timezone
     */
    public static function parse(string $date): Carbon
    {
        return Carbon::parse($date)->setTimezone(self::getTimezone());
    }

    /**
     * Create a Carbon instance from format in the application timezone
     */
    public static function createFromFormat(string $format, string $date): Carbon
    {
        return Carbon::createFromFormat($format, $date, self::getTimezone());
    }

    /**
     * Format current time for display
     */
    public static function formatNow(string $format = 'M j, Y g:i A'): string
    {
        return self::now()->format($format);
    }

    /**
     * Format a date for display
     */
    public static function formatDate(string $date, string $format = 'M j, Y g:i A'): string
    {
        return self::parse($date)->format($format);
    }

    /**
     * Get today's date in Y-m-d format
     */
    public static function today(): string
    {
        return self::now()->format('Y-m-d');
    }

    /**
     * Get a date that is N days before today
     */
    public static function daysBeforeToday(int $days): string
    {
        return self::now()->subDays($days)->format('Y-m-d');
    }

    /**
     * Check if a date is today
     */
    public static function isToday(string $date): bool
    {
        return self::parse($date)->isToday();
    }

    /**
     * Check if a date is in the past
     */
    public static function isPast(string $date): bool
    {
        return self::parse($date)->isPast();
    }

    /**
     * Check if a date is in the future
     */
    public static function isFuture(string $date): bool
    {
        return self::parse($date)->isFuture();
    }
} 