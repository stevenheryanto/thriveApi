<?php

namespace App\Imports\AWB;

use AwbGlobal;
use DB_global;
use App\Models\AWB\awb_trn_point_history;
use App\Models\AWB\awb_tmp_point_history;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PointHistoryImport implements ToModel, WithHeadingRow
{
    public function __construct($platform_id, $user_id)
    {
        $this->platform_id = $platform_id;
        $this->user_id = $user_id;
    }

    public function model(array $row)
    {
        awb_trn_point_history::insert([
            'user_id' => $row['imdl_id'],
            'point' => $row['point'],
            'source' => $row['description'],
            'flag_active'=> 1,
            'user_modified'=> $this->user_id,
            'date_modified'=> DB_global::Global_CurrentDatetime(),
            'status_date'=> DB_global::Global_CurrentDatetime(),
            'platform_id' => $this->platform_id
        ]);
        AwbGlobal::UpdateUserPointAndLevelexport($row['imdl_id'], $this->platform_id);
        return new awb_tmp_point_history([
            'user_id' => $row['imdl_id'],
            'point' => $row['point'],
            'source' => $row['description'],
            'platform_id' => $this->platform_id
        ]);
    }

    public function headingRow(): int
    {
        return 1;
    }
}