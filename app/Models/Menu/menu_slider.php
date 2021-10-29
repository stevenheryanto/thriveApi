<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class menu_slider extends Model
{
    use HasFactory;

    protected $table = 'menu_slider';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
