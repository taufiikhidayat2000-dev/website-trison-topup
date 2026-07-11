export interface ReviewDataItem {
    id: number;
    order_id: number;
    user_id: number | null;
    customer_name: string;
    game_name: string;
    product_name: string;
    rating: number;
    review: string;
    status: 'published' | 'hidden' | 'pending';
    admin_reply?: string | null;
    admin_replied_at?: string | null;
    created_at: string;
    updated_at: string;
    order?: {
        reference: string;
    };
}

export interface ReviewStatisticDataItem {
    average: number;
    total: number;
    distribution: Record<1 | 2 | 3 | 4 | 5, number>;
}
