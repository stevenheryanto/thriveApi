<?php

namespace App\Imports\AWB;

use App\Models\AWB\awb_trn_article_import_detail_temp;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ArticleImport implements ToModel, WithHeadingRow
{
    public function __construct($trn_article_import_id, $platform_id)
    {
        $this->trn_article_import_id = $trn_article_import_id;
        $this->platform_id = $platform_id;
    }

    function transformDate($value, $format = 'Y-m-d H:i:s')
    {
        try {
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return \Carbon\Carbon::createFromFormat($format, $value);
        }
    }

    public function model(array $row)
    {
        return new awb_trn_article_import_detail_temp([
            'trn_article_import_id' => $this->trn_article_import_id,
            'article_action'=> trim(strtoupper($row['action'])),
            'article_id'=> trim($row['content_id']),
            'user_employee_email' => $row['email'],
            'user_employee_id'=> trim($row['imdl_id']),
            'user_employee_name' => $row['full_name'],
            'user_employee_function' => $row['function'],
            'content_datetime' => $this->transformDate($row['date'] + $row['time']),
            'article_comment' => $row['comment'],
            'platform_id' => $this->platform_id
        ]);
    }

    public function headingRow(): int
    {
        return 1;
    }
}