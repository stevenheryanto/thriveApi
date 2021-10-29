<?php

namespace App\Models\TTL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class uft_email_card extends Model
{
    use HasFactory;

    protected $table = 'uft_email_card';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
