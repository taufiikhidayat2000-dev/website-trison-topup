export interface DepositDataItem {
    id: number;
    user_id: number;
    driver: 'linkqu' | 'midtrans';
    reference: string;
    amount: number;
    fee: number;
    total_pay: number;
    channel: string;
    payment_type?: string | null;
    account_number?: string | null;
    account_code?: string | null;
    status: 'pending' | 'paid' | 'expired' | 'failed';
    linkqu_reference?: string | null;
    expired_at?: string | null;
    paid_at?: string | null;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
        email: string;
    };
}
