<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class awb_trn_article_import_detail_temp extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'awb_trn_article_import_detail_temp';
    protected $guarded = [];
}
