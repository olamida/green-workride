<?php

namespace App\Enums;

enum TrustLedgerType: string
{
    // The float behind Time-Bank "ride now, pay later" seats (Design Review 3).
    case TimeBankFloat = 'time_bank_float';

    // 15% of operating profit remitted to the Community Trust (guide §2.1).
    case OperationsProfitShare = 'operations_profit_share';

    // Research fund (RoadLab IRI accuracy, fuel-saving models).
    case ResearchFund = 'research_fund';

    // Free-ride fund for low-income workers.
    case CommunitySubsidy = 'community_subsidy';

    // Unallocated / contingency.
    case Contingency = 'contingency';

    public function label(): string
    {
        return match ($this) {
            self::TimeBankFloat => 'Time-Bank float',
            self::OperationsProfitShare => 'Profit share',
            self::ResearchFund => 'Research fund',
            self::CommunitySubsidy => 'Community subsidy',
            self::Contingency => 'Contingency',
        };
    }
}
