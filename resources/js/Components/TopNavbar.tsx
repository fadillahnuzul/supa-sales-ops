import { usePage } from '@inertiajs/react';
import type { PageProps, UserRole } from '@/types';
import RoleBadge from './RoleBadge';

const roles: UserRole[] = [
    'Industri',
    'SME',
    'Low Cost',
    'All',
];

export default function TopNavbar() {
    const { auth } = usePage<PageProps>().props;

    const user = auth.user;

    return (
        <header className="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6">
            <div className="flex items-center gap-4">
                <img
                    src="/images/supa-logo.png"
                    alt="SUPA"
                    className="w-[68px] object-contain"
                />

                <div>
                    <h1 className="text-[16px] font-bold text-gray-900">
                        PT SUPA SURYA NIAGA
                    </h1>

                    <p className="text-[12px] text-gray-500">
                        Sales, Pricing & Quotation System
                    </p>
                </div>
            </div>

            <div className="flex items-center gap-6">
                <div className="h-9 w-px bg-gray-200" />

                <div className="flex items-center gap-3">
                    <div className="text-right">
                        <p className="text-[12px] font-semibold text-gray-900">
                            {user?.name ?? 'User'}
                        </p>

                        <RoleBadge
                            role={user?.role ?? 'Industri'}
                            className="mt-1"
                        />
                    </div>

                    <img
                        src={
                            user?.photo ??
                            '/images/default-avatar.jpg'
                        }
                        alt={user?.name ?? 'User'}
                        className="h-11 w-11 rounded-full object-cover"
                    />
                </div>
            </div>
        </header>
    );
}