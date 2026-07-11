<?php

namespace App\Actions\Cms\Member;

use App\Models\User;
use App\Models\Wallet\BalanceMutation;
use App\Services\BalanceService;

class AdjustMemberBalanceAction
{
    public function __construct(
        public readonly BalanceService $balanceService,
    ) {}

    /**
     * Handle the action.
     */
    public function handle(User $member, array $data, User $admin): BalanceMutation
    {
        $method = $data['type'] === 'credit' ? 'credit' : 'debit';

        return $this->balanceService->{$method}(
            user: $member,
            amount: (int) $data['amount'],
            description: $data['description'],
            performedBy: $admin,
        );
    }
}
