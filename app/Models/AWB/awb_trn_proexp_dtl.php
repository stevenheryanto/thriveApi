<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class awb_trn_proexp_dtl extends Model
{
    use HasFactory;

    protected $table = 'awb_trn_proexp_dtl';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
