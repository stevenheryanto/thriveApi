<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory;

    protected $table = 'config';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'parameter',
        'value',
        'status_active',
        'user_modified',
        'date_modified'
    ];
}
