import toast from 'react-hot-toast';

import { useMutation } from '@tanstack/react-query';

import { checkoutServices, type SubmitOrderPayload } from '../services/store/checkout.service';

import type { CheckoutSession } from '../types/checkout.types';

import { CHECKOUT_ERROR_MAPPING, REQUIRED_CHECKOUT_FIELDS } from '../configs/checkout.configs';

interface InitiateCheckoutResult {
  paymentUrl: string;
  token: string;
}

interface UseCheckoutActionsParams {
  rawOrders?: SubmitOrderPayload['products'];
  clearCartFlag?: boolean;
  onCheckoutInitiated?: (payload: InitiateCheckoutResult) => void;
  onStockError?: (title: string, message: string) => void;
}

interface CheckoutFormData {
  customerName: string;
  customerPhone: string;
  cityId: string;
  districtId: string;
  address: string;
  discountValue?: number;
  notes?: string;
  [key: string]: unknown;
}

export const useCheckoutActions = ({
  rawOrders = [],
  clearCartFlag = false,
  onCheckoutInitiated,
  onStockError,
}: UseCheckoutActionsParams = {}) => {
  const initiateCheckoutMutation = useMutation<
    InitiateCheckoutResult,
    { response?: { status?: number; data?: { error_code?: string } } },
    SubmitOrderPayload
  >({
    mutationFn: checkoutServices.initiateCheckout,
    onSuccess: (data) => {
      onCheckoutInitiated?.(data);
    },
    onError: (error) => {
      console.error(error);

      if (error?.response?.status === 422) {
        const errorCode = error?.response?.data?.error_code;
        const mappedError = errorCode ? CHECKOUT_ERROR_MAPPING[errorCode] : undefined;

        if (mappedError && onStockError) {
          onStockError(mappedError.title, mappedError.message);
          return;
        }
      }

      toast.error('Failed to submit order. Please check your information.', {
        id: 'submit-api-error',
      });
    },
  });

  const markCartClearedMutation = useMutation<CheckoutSession, Error, string>({
    mutationFn: checkoutServices.markCartCleared,
    onError: (error) => {
      console.error('Failed to mark cart as cleared on the checkout session', error);
    },
  });

  const submitOrder = (data: CheckoutFormData): void => {
    if (REQUIRED_CHECKOUT_FIELDS.some((field: string) => !data[field])) {
      toast.error('Please fill in all required fields', { id: 'submit-validation-error' });
      return;
    }

    const payload: SubmitOrderPayload = {
      customerName: data.customerName,
      customerPhone: data.customerPhone,
      cityId: parseInt(data.cityId, 10),
      districtId: parseInt(data.districtId, 10),
      address: data.address,
      clearCart: clearCartFlag,
      products: rawOrders,
      discountValue: data.discountValue || 0,
      notes: data.notes || '',
    };

    initiateCheckoutMutation.mutate(payload);
  };

  const markCartCleared = (token: string): void => {
    markCartClearedMutation.mutate(token);
  };

  return {
    submitOrder,
    markCartCleared,
    isSubmitting: initiateCheckoutMutation.isPending,
    isMarkingCartCleared: markCartClearedMutation.isPending,
  };
};
