import type { LucideIcon } from 'lucide-react';

export type CheckoutCallbackStatus = 'loading' | 'success' | 'failure';

export type LockedOrderData = {
  status: CheckoutCallbackStatus | null;
  orderNumber: string;
};



export type CallbackContentConfig = {
  icon: LucideIcon;
  stroke: string;
  title: string;
  message: string;
  primaryAction: { label: string; actionKey: 'goToStore' | 'goToCart' };
  secondaryAction: { label: string; actionKey: 'goToHome' };
};
