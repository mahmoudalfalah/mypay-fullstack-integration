import { useQuery, type UseQueryResult } from '@tanstack/react-query';

import { checkoutServices } from '../services/store/checkout.service';

import type { CheckoutSession } from '../types/checkout.types';

const checkoutStatusKey = (token: string | null): readonly ['checkout', 'status', string | null] =>
  ['checkout', 'status', token] as const;

export const useCheckoutStatus = (
  token: string | null,
): UseQueryResult<CheckoutSession, Error> => {
  return useQuery<CheckoutSession, Error>({
    queryKey: checkoutStatusKey(token),
    queryFn: () => checkoutServices.getCheckoutStatus(token as string),
    enabled: !!token,
    refetchOnWindowFocus: false,
    staleTime: 0,
    retry: 1,
  });
};
