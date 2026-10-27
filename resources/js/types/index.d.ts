import type { PageProps as InertiaPageProps } from '@inertiajs/core';

export type UserRole = 'Industri' | 'SME' | 'Low Cost' | 'All';

export interface User {
    id: number;
    name: string;
    email?: string;
    role?: UserRole;
    role_id?: number | null;
    photo?: string;
    segmentation_ids: number[] | [];
    segmentation_names: string[] | [];
}

export interface PageProps extends InertiaPageProps {
    auth: {
        user: User | null;
    };
}