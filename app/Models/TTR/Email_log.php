<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email_log extends Model
{
    use HasFactory;
    protected $table = 'email_log';
    public $timestamps = false;
}
