<?php

namespace App\Exports\AWB;

use App\Models\AWB\awb_trn_redeem_code;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RedeemCodeExport implements Responsable, FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Claim Id',
            'User Id',
            'User Account',
            'User Name',
            'Point',
            'Redeem Claim Date',
        ];
    }

    public function map($result): array
    {
        return [
            $result->id,
            $result->user_id,
            $result->user_account,
            $result->name,
            $result->points,
            $result->date_redeem,
        ];
    }

    public function __construct($platform_id, $filter_period_from, $filter_period_to)
    {
        $this->platform_id = $platform_id;
        $this->filter_period_from = $filter_period_from;
        $this->filter_period_to = $filter_period_to;
    }

    public function query()
    {
        return awb_trn_redeem_code::query()
            ->selectRaw('awb_trn_redeem_code.*, ifnull(b.name,b.full_name) as name, b.account as user_account, b.id as user_id')
            ->leftJoin('users as b', 'awb_trn_redeem_code.user_id', '=', 'b.id')
            ->where('awb_trn_redeem_code.platform_id', '=', $this->platform_id)
            ->when(isset($this->filter_period_from, $this->filter_period_to),
                function ($query) {
                $query->whereBetween(DB::raw('convert(awb_trn_redeem_code.date_redeem,date)'), [$this->filter_period_from, $this->filter_period_to]);
            });
    }
}
