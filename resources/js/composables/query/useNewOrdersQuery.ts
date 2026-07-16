import type { UseQueryReturnType } from '@tanstack/vue-query';
import { useQuery } from '@tanstack/vue-query';
import axios from 'axios';
import { Ref } from 'vue';

export interface NewOrderItem {
    id: number;
    reference: string;
    name: string;
    total_amount: number;
    payment_status: number;
    order_type: 'topup' | 'gift' | 'manual' | 'unknown';
    created_at: string;
}

interface PollNewOrdersResponse {
    orders: NewOrderItem[];
    last_id: number;
}

/**
 * Polls the All Orders "poll-new" endpoint every few seconds for orders
 * created after `afterId`. Runs even while the tab is unfocused so a
 * desktop notification can still fire for orders placed while the admin is
 * away from the tab.
 */
export default function useNewOrdersQuery(
    afterId: Ref<number>,
): UseQueryReturnType<PollNewOrdersResponse, Error> {
    return useQuery({
        queryKey: ['cms-all-orders-poll-new'],
        queryFn: async () => {
            const res = await axios.get<PollNewOrdersResponse>(
                '/cms/order/all-orders/poll-new',
                { params: { after_id: afterId.value } },
            );

            return res.data;
        },
        refetchInterval: 8000,
        refetchIntervalInBackground: true,
        refetchOnWindowFocus: false,
    });
}
