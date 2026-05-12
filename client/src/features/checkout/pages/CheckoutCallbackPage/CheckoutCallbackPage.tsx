import { Button } from '@/components/ui/Button/Button';
import { Card } from '@/components/ui/Card/Card';
import { IconBadge } from '@/components/ui/IconBadge/IconBadge';
import { PageContainer } from '@/components/ui/PageContainer/PageContainer';
import { Section } from '@/components/ui/Section/Section';
import { SectionHeader } from '@/components/ui/SectionHeader/SectionHeader';
import { Typography } from '@/components/ui/Typography/Typography';

import { useCheckoutCallbackPage } from '../../hooks/useCheckoutCallbackPage';
import { CHECKOUT_CALLBACK_TITLES, CHECKOUT_CALLBACK_CONTENT } from '../../configs/checkout-callback.configs';
import { CheckoutCallbackSkeleton } from './CheckoutCallbackSkeleton';

import styles from './CheckoutCallbackPage.module.css';

const CheckoutCallbackPage: React.FC = () => {
  const { status, orderNumber, actions } = useCheckoutCallbackPage();
  const content = status && status !== 'loading' ? CHECKOUT_CALLBACK_CONTENT[status] : null;


  return (
    <PageContainer variant="narrow" paddingVariant="block" className={styles.page}>
      <Section marginStyle="bottom">
        <SectionHeader title={CHECKOUT_CALLBACK_TITLES[status ?? 'loading']} />
      </Section>

      <div className={styles.content}>
        <Card className={styles.card}>
          {status === 'loading' && <CheckoutCallbackSkeleton />}

          {content && (
            <>
              <IconBadge icon={content.icon} size={40} padding="lg" stroke={content.stroke} />
              <Typography variant="h4" as="h2" className={styles.title}>{content.title}</Typography>
              <Typography variant="body2" className={styles.message}>{content.message}</Typography>
              
              {status === 'success' && orderNumber && (
                <Typography variant="body2" className={styles.meta}>
                  Order Number: <Typography as="strong" variant="body2" weight="semibold">{orderNumber}</Typography>
                </Typography>
              )}

              <div className={styles.actions}>
                <Button type="button" onClick={actions[content.primaryAction.actionKey]}>
                  {content.primaryAction.label}
                </Button>
                <Button type="button" onClick={actions[content.secondaryAction.actionKey]} outlined>
                  {content.secondaryAction.label}
                </Button>
              </div>
            </>
          )}
        </Card>
      </div>
    </PageContainer>
  );
};

export default CheckoutCallbackPage;