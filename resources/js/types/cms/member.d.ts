export interface MemberDataItem {
    id: number;
    name: string;
    email: string;
    phone?: string;
    balance: number;
    is_active: boolean;
    orders_count?: number;
    created_at: string;
    updated_at: string;
}

export interface BalanceMutationDataItem {
    id: number;
    user_id: number;
    type: 'credit' | 'debit';
    amount: number;
    balance_before: number;
    balance_after: number;
    description: string;
    reference_type?: string | null;
    reference_id?: number | null;
    performed_by: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    updated_at: string;
}
