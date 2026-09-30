import type { PageProps as InertiaPageProps } from '@inertiajs/core';

export type UserRole = 'INDUSTRY' | 'SME' | 'LOW COST' | 'ALL';

export interface User {
    id: number;
    name: string;
    email?: string;
    role?: UserRole;
    photo?: string;
}

export interface PageProps extends InertiaPageProps {
    auth: {
        user: User | null;
    };
}