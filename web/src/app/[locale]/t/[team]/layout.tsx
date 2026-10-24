import { AppShell } from '@/components/app-shell';

export default function TeamLayout({ children }: LayoutProps<'/[locale]/t/[team]'>) {
    return <AppShell>{children}</AppShell>;
}
