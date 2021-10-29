<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class menu_news extends Model
{
    use HasFactory;

    protected $table = 'menu_news';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
