<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class menu_app extends Model
{
    use HasFactory;

    protected $table = 'menu_app';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
