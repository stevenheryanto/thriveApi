<?php

namespace App\Models\TTL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dialogue_event_schedule extends Model
{
    use HasFactory;

    protected $table = 'dialogue_event_schedule';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
