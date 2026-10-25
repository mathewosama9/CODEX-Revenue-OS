import { LegalDocument, legalMetadata } from '@/components/site/legal-document';

// A draft awaiting legal review: always noindex (docs/website/site-plan.md §3.10).
export const generateMetadata = () => legalMetadata('terms');

export default function TermsPage() {
    return <LegalDocument doc="terms" />;
}
