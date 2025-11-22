<?php
// FILE: /app/helpers/DateHelper.php

/**
 * DateHelper class
 *
 * Provides date and time utility functions.
 */
class DateHelper
{
    /**
     * Format date
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function format($date, $format = 'Y-m-d H:i:s')
    {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return '';
        }

        $timestamp = strtotime($date);
        return date($format, $timestamp);
    }

    /**
     * Format date to human readable format
     *
     * @param string $date
     * @return string
     */
    public static function humanReadable($date)
    {
        return self::format($date, 'F j, Y');
    }

    /**
     * Format date and time to human readable format
     *
     * @param string $date
     * @return string
     */
    public static function humanReadableWithTime($date)
    {
        return self::format($date, 'F j, Y g:i A');
    }

    /**
     * Get time ago string
     *
     * @param string $date
     * @return string
     */
    public static function timeAgo($date)
    {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return '';
        }

        $timestamp = strtotime($date);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        }

        if ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        }

        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }

    /**
     * Convert date to UTC
     *
     * @param string $date
     * @param string $timezone
     * @return string
     */
    public static function toUTC($date, $timezone = 'UTC')
    {
        try {
            $dt = new DateTime($date, new DateTimeZone($timezone));
            $dt->setTimezone(new DateTimeZone('UTC'));
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Convert UTC date to timezone
     *
     * @param string $date
     * @param string $timezone
     * @return string
     */
    public static function fromUTC($date, $timezone = 'UTC')
    {
        try {
            $dt = new DateTime($date, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone($timezone));
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Get current date and time
     *
     * @param string $format
     * @return string
     */
    public static function now($format = 'Y-m-d H:i:s')
    {
        return date($format);
    }

    /**
     * Calculate days between two dates
     *
     * @param string $date1
     * @param string $date2
     * @return int
     */
    public static function daysBetween($date1, $date2)
    {
        $timestamp1 = strtotime($date1);
        $timestamp2 = strtotime($date2);
        $diff = abs($timestamp1 - $timestamp2);

        return floor($diff / 86400);
    }

    /**
     * Check if date is in the past
     *
     * @param string $date
     * @return bool
     */
    public static function isPast($date)
    {
        return strtotime($date) < time();
    }

    /**
     * Check if date is in the future
     *
     * @param string $date
     * @return bool
     */
    public static function isFuture($date)
    {
        return strtotime($date) > time();
    }
}
