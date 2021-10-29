<?php

namespace App\Exports\AWB;

use Throwable;
use App\Models\AWB\awb_trn_reward_claim;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Excel;

class RedeemRewardExport implements Responsable, FromQuery, WithHeadings, WithMapping, ShouldQueue
{
    use Exportable;

    private $fileName = 'report_redeem_reward.xlsx';
    private $writerType = Excel::XLSX;
    private $headers = [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function failed(Throwable $th): void
    {
        Storage::disk('s3')->put('learn/log/RedeemRewardExport_'.date('Ymdhms').'.txt', $th, 'public');
    }

    public function headings(): array
    {
        return [
            'Log Id',
            'User Id',
            'User Account',
            'User Name',
            'User Email',
            'Claim Date',
            'Claim Points',
            'Reward',
        ];
    }

    public function map($result): array
    {
        return [
            $result->id,
            $result->user_id,
            $result->user_account,
            $result->user_name,
            $result->user_email,
            $result->claim_date,
            $result->claim_point,
            $result->reward,
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
        return awb_trn_reward_claim::query()
            ->select(
                'awb_trn_reward_claim.id', 'b.id as user_id', 'd.claim_point', 'd.title as reward', 'awb_trn_reward_claim.claim_date',
		 		'b.name as user_name', 'b.account as user_account', 'b.email as user_email',
				'b.directorate as user_function'
                )
            ->join('users as b', 'b.id', '=', 'awb_trn_reward_claim.user_created')
            ->leftJoin('awb_trn_reward as d', 'd.id', '=', 'awb_trn_reward_claim.reward_id')
            ->where('awb_trn_reward_claim.platform_id', '=', $this->platform_id)
            ->when(isset($this->filter_period_from, $this->filter_period_to),
                function ($query) {
                $query->whereBetween(DB::raw('convert(awb_trn_reward_claim.claim_date,date)'), [$this->filter_period_from, $this->filter_period_to]);
            });
    }
}
