import { useEffect, useRef, useState } from 'react';
import { useForm } from 'react-hook-form';
import toast from 'react-hot-toast';
import { useLocation } from 'react-router-dom';

import { useCartActions } from '@/features/cart';
import { useLocations } from '@/features/locations/hooks/shared/useLocations';

import { useModalManager } from '@/hooks/useModalManager';
import { useStoreRouting } from '@/hooks/useStoreRouting';

import { useCheckoutActions } from './useCheckoutActions';
import { useCheckoutCart } from './useCheckoutCart';

import { DEFAULT_CHECKOUT_VALUES } from '../configs/checkout.configs';

export const useCheckoutPage = () => {
  const location = useLocation();
  const { goToCart, goToStore, goToProduct, replaceRoute } = useStoreRouting();
  const { state: globalCartState, clearCart: clearGlobalCart } = useCartActions();

  const [checkoutContext] = useState(() => {
    const routeState = location?.state || {};
    return {
      pass: routeState.pass,
      clearCartFlag: routeState.clear,
      fastOrder: routeState.cart,
      isDirectBuy: !!routeState.cart,
    };
  });

  const { pass, clearCartFlag, fastOrder, isDirectBuy } = checkoutContext;

  useEffect(() => {
    if (!pass) goToCart({ replace: true });
  }, [pass, goToCart]);

  const modalManager = useModalManager();
  const { openModal, closeModal } = modalManager;
  const form = useForm({ defaultValues: DEFAULT_CHECKOUT_VALUES });
  const currentCityId = form.watch('cityId');

  const {
    rawOrders,
    hydratedItems,
    totalPrice,
    loadingCart,
    hasStockError,
    deletedIds,
    invisibleIds,
  } = useCheckoutCart(fastOrder, globalCartState);

  const sourceProductSlug = isDirectBuy && hydratedItems.length > 0 ? hydratedItems[0]?.slug : null;

  const { cities, districts, isLoadingCities, isLoadingDistricts } = useLocations(currentCityId);

  const { submitOrder, isSubmitting } = useCheckoutActions({
    rawOrders,
    clearCartFlag,
    onCheckoutInitiated: (payload) => {
      localStorage.setItem('checkout_token', payload.token);
      window.location.replace(payload.paymentUrl);
    },
    onStockError: (title, message) => openModal('stockWarning', { title, message }),
  });

  const deletedIdsStr = deletedIds.join(',');
  const invisibleIdsStr = invisibleIds.join(',');

  const hasRedirected = useRef(false);

  useEffect(() => {
    if (
      !loadingCart &&
      (deletedIds.length > 0 || invisibleIds.length > 0) &&
      !hasRedirected.current
    ) {
      hasRedirected.current = true;

      const message = isDirectBuy
        ? 'This product is no longer available. Returning you to the store.'
        : 'Cart update required. Returning you to your cart for review.';

      toast.error(message, { id: 'checkout-integrity-error' });

      isDirectBuy ? goToStore(undefined, { replace: true }) : goToCart({ replace: true });
    }
  }, [loadingCart, deletedIdsStr, invisibleIdsStr, isDirectBuy, goToStore, goToCart]);

  useEffect(() => {
    const isOnlyStockError = hasStockError && deletedIds.length === 0 && invisibleIds.length === 0;

    if (!loadingCart && isOnlyStockError) {
      const message = isDirectBuy
        ? 'Sorry, the requested quantity is no longer available. Please adjust your order on the product page.'
        : 'Sorry, some products in your order are no longer available in the required quantity. Please return to the cart.';
      openModal('stockWarning', {
        title: 'Stock Update',
        message: message,
        actionLabel: isDirectBuy ? 'Return to Product' : 'Return to Cart',
      });
    }
  }, [loadingCart, hasStockError, deletedIdsStr, invisibleIdsStr, isDirectBuy, openModal]);

  const handleStockWarningConfirm = () => {
    closeModal();
    if (isDirectBuy && sourceProductSlug) {
      goToProduct(sourceProductSlug, { replace: true });
    } else {
      goToCart({ replace: true });
    }
  };

  return {
    form,
    totalPrice,
    hydratedItems,
    rawOrders,
    cities,
    districts,
    loadingState: {
      cities: isLoadingCities,
      districts: isLoadingDistricts,
      cart: loadingCart,
      submitting: isSubmitting,
    },
    hasStockError,
    actions: {
      submitOrder: form.handleSubmit(submitOrder),
      clearGlobalCart,
      goToCart,
      clearCartFlag,
      handleStockWarningConfirm,
    },
    modalManager,
  };
};
