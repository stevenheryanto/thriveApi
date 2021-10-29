<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class awb_trn_article_spesific_user extends Model
{
    use HasFactory;

    protected $table = 'awb_trn_article_spesific_user';
    const CREATED_AT = 'date_created';
}
