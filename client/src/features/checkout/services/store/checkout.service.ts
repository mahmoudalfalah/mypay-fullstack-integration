import { axios } from '@/lib/axios';

import type { CheckoutSession } from '../../types/checkout.types';

export interface SubmitOrderPayload {
  customerName: string;
  customerPhone: string;
  customerEmail?: string;
  cityId: number;
  districtId: number;
  address: string;
  products: { productId: string | number; quantity: number }[];
  discountValue?: number;
  notes?: string;
  clearCart?: boolean;
}

export const checkoutServices = {
  initiateCheckout: async (payload: SubmitOrderPayload) => {  
    const backendPayload = {
      customer_name: payload.customerName,
      customer_phone: payload.customerPhone,
      customer_email: payload.customerEmail || undefined,
      city_id: payload.cityId,
      district_id: payload.districtId,
      address: payload.address,

      products: payload.products.map((item) => ({
        product_id: item.productId,
        quantity: item.quantity,
      })),

      discount_value: payload.discountValue || 0,
      notes: payload.notes || '',
      clear_cart: payload.clearCart || false,
    };
    const response = await axios.post('/checkout', backendPayload, { authType: 'noAuth' });


    const data = response.data.data || response.data;
    return {
      paymentUrl: data.payment_url,
      token: data.token,
    };
  },
  getCheckoutStatus: async (token: string): Promise<CheckoutSession> => {
    const response = await axios.get(`/checkout/${token}`);
    return response.data?.data as CheckoutSession;
  },
  markCartCleared: async (token: string): Promise<CheckoutSession> => {
    const response = await axios.patch(`/checkout/${token}/cart-cleared`);
    return response.data?.data as CheckoutSession;
  },
};
