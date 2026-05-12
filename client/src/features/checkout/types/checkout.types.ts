export type CheckoutSessionStatus =
  | 'initiated'
  | 'payment_pending'
  | 'completed'
  | 'expired';

export interface CheckoutSession {
  id: number;
  token: string;
  paymentId: string | null;
  orderNumber: string;
  orderData: Record<string, unknown>;
  status: CheckoutSessionStatus;
  expiresAt: string;
  clearCart: boolean;
  cartClearedAt: string | null;
  createdAt: string;
  updatedAt: string;
}

