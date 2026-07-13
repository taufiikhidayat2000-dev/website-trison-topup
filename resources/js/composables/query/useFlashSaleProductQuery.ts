import type { UseQueryReturnType } from '@tanstack/vue-query';
import { useQuery } from '@tanstack/vue-query';
import axios from 'axios';
import { MaybeRef, unref } from 'vue';

export default function useFlashSaleProductQuery() {
    const searchProducts = (
        flashSaleId: MaybeRef<number>,
        search: MaybeRef<string>,
        categoryId: MaybeRef<number | null>,
        brandId: MaybeRef<number | null>,
    ): UseQueryReturnType<any[], Error> => {
        return useQuery({
            queryKey: ['flashSaleProductSearch', flashSaleId, search, categoryId, brandId],
            queryFn: async () => {
                const res = await axios.get(
                    `/cms/marketing/flash-sales/${unref(flashSaleId)}/products/search`,
                    {
                        params: {
                            search: unref(search) || undefined,
                            category_id: unref(categoryId) || undefined,
                            brand_id: unref(brandId) || undefined,
                        },
                    },
                );

                if (res.status !== 200) {
                    throw new Error('Failed to search products');
                }

                return res.data.data;
            },
        });
    };

    return {
        searchProducts,
    };
}
