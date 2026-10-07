import { Link, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import type { PageProps } from '@/types';
import RoleBadge from './RoleBadge';

import {
    LayoutDashboard,
    Database,
    ClipboardList,
    Printer,
    UserCog,
    LogOut,
    ChevronLeft,
    ChevronRight,
    type LucideIcon,
} from 'lucide-react';

interface MenuItem {
    label: string;
    href: string;
    icon: LucideIcon;
    active: boolean;
}

interface SidebarProps {
    isOpen: boolean;
    onToggle: () => void;
}

export default function Sidebar({ isOpen, onToggle }: SidebarProps) {
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
            href: route('inquiry-print'),
            icon: Printer,
            active:
                route().current(
                    'inquiry-print'
                ) === true,
        },
        {
            label: 'Profil & Roles',
            href: '#',
            icon: UserCog,
            active: false,
        },
    ];

    return (
        <aside
            className={`relative flex h-full flex-col bg-[#182231] py-2 text-white ${
                isOpen ? 'w-[200px] px-2' : 'w-[80px] px-2'
            }`}
        >
            <div>
                <div className="relative mb-3 flex h-10 items-center justify-center">
                    {isOpen ? (
                        <h2 className="px-2 text-[14px] font-bold">
                            Menu Aplikasi
                        </h2>
                    ) : (
                        <img
                            src="/images/supa-logo.png"
                            alt="SUPA"
                            className="w-11 object-contain"
                        />
                    )}

                    <button
                        type="button"
                        aria-label={isOpen ? 'Tutup navigasi' : 'Buka navigasi'}
                        aria-expanded={isOpen}
                        aria-controls="app-sidebar"
                        onClick={onToggle}
                        className="absolute right-[-22px] z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white text-gray-700 shadow-md transition hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#20A36B]"
                    >
                        {isOpen ? (
                            <ChevronLeft size={18} />
                        ) : (
                            <ChevronRight size={18} />
                        )}
                    </button>
                </div>

                <nav className="space-y-2">
                    {menus.map((menu) => {
                        const Icon = menu.icon;

                        return (
                            <Link
                                key={menu.label}
                                href={menu.href}
                                aria-label={menu.label}
                                title={!isOpen ? menu.label : undefined}
                                className={`
                                    flex h-[52px] items-center
                                    ${isOpen ? 'gap-4 rounded-xl px-4' : 'justify-center rounded-lg px-0'}
                                    text-[14px] font-semibold
                                    transition
                                    ${menu.active
                                        ? 'bg-[#20A36B] text-white'
                                        : 'text-gray-200 hover:bg-white/10'
                                    }
                                `                                }
                            >
                                <Icon size={22} />
                                <span className={isOpen ? '' : 'sr-only'}>
                                    {menu.label}
                                </span>
                            </Link>
                        );
                    })}
                </nav>
            </div>

            <div className="mt-auto">
                <button
                    type="button"
                    title="Logout"
                    className={`flex h-10 items-center justify-center rounded-xl bg-red-500 text-[12px] font-bold hover:bg-red-600 ${
                        isOpen ? 'w-full gap-2' : 'mx-auto w-11'
                    }`}
                >
                    {isOpen ? <LogOut size={17} /> : null}
                    {isOpen ? 'LOGOUT' : 'Logout'}
                </button>

                <hr className="my-2" />

                <div
                    className={`mb-2 flex items-center ${
                        isOpen ? 'gap-3 px-2' : 'justify-center'
                    }`}
                >
                    <img
                        src={
                            user?.photo ??
                            '/images/default-avatar.jpg'
                        }
                        alt={user?.name ?? 'User'}
                        className="h-11 w-11 rounded-full object-cover"
                    />
                    {isOpen && (
                        <div>
                            <p className="text-[12px] font-semibold text-light-900">
                                {user?.name ?? 'User'}
                            </p>

                            <RoleBadge
                                role={user?.role ?? 'Industri'}
                                className="mt-1"
                            />
                        </div>
                    )}
                </div>
            </div>
        </aside>
    );
}