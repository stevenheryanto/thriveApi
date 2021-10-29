<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recognize_email_card extends Model
{
    use HasFactory;

    protected $table = 'recognize_email_card';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
