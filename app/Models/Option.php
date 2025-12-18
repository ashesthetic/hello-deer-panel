<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get an option value by key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $option = self::where('key', $key)->first();
        return $option ? $option->value : $default;
    }

    /**
     * Set an option value
     * 
     * @param string $key
     * @param mixed $value
     * @return Option
     */
    public static function set(string $key, $value): Option
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Check if an option exists
     * 
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Delete an option
     * 
     * @param string $key
     * @return bool
     */
    public static function remove(string $key): bool
    {
        return self::where('key', $key)->delete();
    }
}
