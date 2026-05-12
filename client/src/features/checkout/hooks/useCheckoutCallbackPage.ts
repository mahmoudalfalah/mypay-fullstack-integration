import { useEffect, useMemo, useRef } from 'react';
import { useSearchParams } from 'react-router-dom';

import { useCartActions } from '@/features/cart';

import { useStoreRouting } from '@/hooks/useStoreRouting';

import { useCheckoutActions } from './useCheckoutActions';
import { useCheckoutStatus } from './useCheckoutStatus';

import type { CheckoutCallbackStatus } from '../types/checkout-callback.types';
import type { LockedOrderData } from '../types/checkout-callback.types';


type UseCheckoutCallbackPageResult = {
  status: CheckoutCallbackStatus | null;
  orderNumber: string;
  actions: {
    goToHome: () => void;
    goToStore: () => void;
    goToCart: () => void;
  };
}

export const useCheckoutCallbackPage = (): UseCheckoutCallbackPageResult => {
  const [searchParams] = useSearchParams();
  const { goToHome, goToStore, goToCart } = useStoreRouting();
  const { clearCart: clearGlobalCart } = useCartActions();
  const { markCartCleared } = useCheckoutActions();
  const lockedDataRef = useRef<LockedOrderData>({ status: null, orderNumber: '' });
  

  const token: string | null = searchParams.get('token');

  const { data, isLoading, isError } = useCheckoutStatus(token);

  const orderStatus: LockedOrderData = useMemo(() => {
  if (lockedDataRef.current.status === 'success') return lockedDataRef.current;
  if (!token) return { status: 'failure', orderNumber: '' };
  if (isLoading) return { status: 'loading', orderNumber: '' };
  if (isError || !data) return { status: 'failure', orderNumber: '' };

  const currentStatus = data.status === 'completed' ? 'success' : 'failure';
  
  if (currentStatus === 'success') {
    lockedDataRef.current = { status: 'success', orderNumber: data.orderNumber || '' };
  }
  
  return { status: currentStatus, orderNumber: data.orderNumber || '' };
}, [token, isLoading, isError, data]);

  const hasClearedCartRef = useRef<boolean>(false);

  useEffect(() => {
    if (
      orderStatus.status === 'success' &&
      data?.clearCart === true &&
      !hasClearedCartRef.current &&
      !data?.cartClearedAt &&
      token
    ) {
      hasClearedCartRef.current = true;
      clearGlobalCart();
      markCartCleared(token);
    }
  }, [orderStatus, data?.clearCart, token, clearGlobalCart, markCartCleared]);

  return {
    status: orderStatus.status,
    orderNumber: orderStatus.orderNumber || '',
    actions: {
      goToHome: () => goToHome(),
      goToStore: () => goToStore(),
      goToCart: () => goToCart(),
    },
  };
};
