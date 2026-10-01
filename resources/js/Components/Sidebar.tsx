import { Link, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import type { PageProps, UserRole } from '@/types';
import RoleBadge from './RoleBadge';

import {
    LayoutDashboard,
    Database,
    ClipboardList,
    Printer,
    UserCog,
    LogOut,
    type LucideIcon,
} from 'lucide-react';

interface MenuItem {
    label: string;
    href: string;
    icon: LucideIcon;
    active: boolean;
}

export default function Sidebar() {

    const { auth } = usePage<PageProps>().props;
    const user = auth.user;

    const menus: MenuItem[] = [
        {
            label: 'Dashboard',
            href: route('dashboard'),
            icon: LayoutDashboard,
            active: route().current('dashboard'),
        },
        {
            label: 'Database Center',
            href: route('database-center'),
            icon: Database,
            active:
                route().current(
                    'database-center'
                ) === true,
        },
        {
            label: 'Inquiry',
            href: route('inquiry'),
            icon: ClipboardList,
            active:
                route().current(
                    'inquiry'
                ) === true,
        },
        {
            label: 'Print',
            href: '#',
            icon: Printer,
            active: false,
        },
        {
            label: 'Profil & Roles',
            href: '#',
            icon: UserCog,
            active: false,
        },
    ];

    return (
        <aside className="flex w-[200px] flex-col bg-[#182231] px-2 py-2 text-white">
            <div>
                <h2 className="mb-3 px-2 text-[14px] font-bold">
                    Menu Aplikasi
                </h2>

                <nav className="space-y-2">
                    {menus.map((menu) => {
                        const Icon = menu.icon;

                        return (
                            <Link
                                key={menu.label}
                                href={menu.href}
                                className={`
                                    flex h-[52px] items-center gap-4
                                    rounded-xl px-4
                                    text-[14px] font-semibold
                                    transition
                                    ${menu.active
                                        ? 'bg-[#20A36B] text-white'
                                        : 'text-gray-200 hover:bg-white/10'
                                    }
                                `}
                            >
                                <Icon size={22} />

                                <span>{menu.label}</span>
                            </Link>
                        );
                    })}
                </nav>
            </div>

            <div className="mt-auto">
                {/* <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="
                        flex h-10 w-full
                        items-center justify-center gap-2
                        rounded-md
                        bg-red-500
                        text-[12px] font-bold
                        hover:bg-red-600
                    "
                >
                    <LogOut size={17} />
                    LOGOUT
                </Link> */}
                <button
                    type="button"
                    className="
        flex h-10 w-full
        items-center justify-center gap-2
        rounded-md
        bg-red-500
        text-[12px] font-bold
        hover:bg-red-600
    "
                >
                    <LogOut size={17} />
                    LOGOUT
                </button>

                <hr className="my-2" />

                <div className="flex items-center gap-3 mb-2 px-2">
                    <img
                        src={
                            user?.photo ??
                            '/images/default-avatar.jpg'
                        }
                        alt={user?.name ?? 'User'}
                        className="h-11 w-11 rounded-full object-cover"
                    />
                    <div className="">
                        <p className="text-[12px] font-semibold text-light-900">
                            {user?.name ?? 'User'}
                        </p>

                        <RoleBadge
                            role={user?.role ?? 'Industri'}
                            className="mt-1"
                        />
                    </div>
                </div>
            </div>
        </aside>
    );
}