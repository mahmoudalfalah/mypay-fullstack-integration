import { CheckCircle2, XCircle } from 'lucide-react';
import type { CheckoutCallbackStatus } from '../types/checkout-callback.types';
import type { CallbackContentConfig } from '../types/checkout-callback.types';

export const CHECKOUT_CALLBACK_TITLES: Record<CheckoutCallbackStatus, string> = {
  loading: 'Processing Payment…',
  success: 'Order Confirmed',
  failure: 'Payment Unsuccessful',
} as const;

export const CHECKOUT_CALLBACK_CONTENT: Record<Exclude<CheckoutCallbackStatus, 'loading'>, CallbackContentConfig> = {
  success: {
    icon: CheckCircle2,
    stroke: 'var(--success)',
    title: 'Thank you! Your order is confirmed.',
    message: 'Your payment was successful and your order has been placed. A confirmation will be sent to you shortly.',
    primaryAction: { label: 'Continue Shopping', actionKey: 'goToStore' },
    secondaryAction: { label: 'Back to Home', actionKey: 'goToHome' },
  },
  failure: {
    icon: XCircle,
    stroke: 'var(--danger)',
    title: 'Payment was not completed',
    message: 'We could not confirm your payment. Your order has not been placed. You can return to your cart and try again.',
    primaryAction: { label: 'Back to Cart', actionKey: 'goToCart' },
    secondaryAction: { label: 'Back to Home', actionKey: 'goToHome' },
  },
};