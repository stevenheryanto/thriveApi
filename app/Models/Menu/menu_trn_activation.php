<?php

namespace App\Models\Menu;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class menu_trn_activation extends Model
{
    use HasFactory;

    protected $table = 'menu_trn_activation';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
