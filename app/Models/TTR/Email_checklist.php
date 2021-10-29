<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email_checklist extends Model
{
    use HasFactory;
    protected $table = 'email_checklist';
    public $timestamps = false;
}
