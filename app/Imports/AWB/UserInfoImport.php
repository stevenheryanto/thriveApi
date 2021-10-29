<?php

namespace App\Imports\AWB;

use DB_global;
use App\Models\AWB\awb_users_info;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;

class UserInfoImport implements ToModel, WithHeadingRow, WithUpserts
{
    public function __construct($user_id, $platform_id)
    {
        $this->user_id = $user_id;
        $this->platform_id = $platform_id;
    }

    public function uniqueBy()
    {
        return 'id';
    }

    public function model(array $row)
    {
        return new awb_users_info([
            'id' => trim($row['imdl_id']),
            'group_grade'=> trim(strtoupper($row['group_grade'])),
            'group_function'=> ucwords(trim(strtolower($row['group_function']))),
            'group_yos'=> trim(strtoupper($row['group_yos'])),
            'generation'=> ucwords(trim(strtolower($row['generation']))),
            'gender'=>  ucwords(trim(strtolower($row['gender']))),
            'group_basetown_location'=> trim($row['group_basetown_location']),
            'user_created'=> $this->user_id,
            'date_created'=> DB_global::Global_CurrentDatetime(),
            'platform_id' => $this->platform_id,
        ]);
    }

    public function headingRow(): int
    {
        return 1;
    }
}