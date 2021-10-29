<?php

namespace App\Exports\AWB;

use App\Models\AWB\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class UnmatchedUserExport implements Responsable, FromQuery, WithHeadings, WithMapping, WithStrictNullComparison
{
    use Exportable;

    public function headings(): array
    {
        return [
            'IMDL Id',
            'Email',
            'Title',
            'Business Unit',
            'Directorate'
        ];
    }

    public function map($result): array
    {
        return [
            $result->id,
            $result->email,
            $result->title,
            $result->business_unit,
            $result->directorate
        ];
    }


    public function __construct($platform_id)
    {
        $this->platform_id = $platform_id;
    }

    public function query()
    {
        return User::query()
            ->select('users.id', 'users.email', 'users.title', 'users.business_unit', 'users.directorate')
            ->leftJoin('awb_users_info as b', function($join){
                $join->on('b.id', '=', 'users.id')
                    ->where('b.platform_id', '=', $this->platform_id);
            })
            ->whereNull('b.id')
            ->where('users.employee_status', '=', 'permanent')
            ->where('users.status_active', '=', 1);
    }
}
