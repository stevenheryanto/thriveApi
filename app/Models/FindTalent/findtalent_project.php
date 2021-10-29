<?php

namespace App\Models\FindTalent;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class findtalent_project extends Model
{
    use findtalent_project;

    protected $table = 'findtalent_project';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
