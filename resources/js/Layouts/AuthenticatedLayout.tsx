import type { ReactNode } from 'react';

import Sidebar from '@/Components/Sidebar';
import TopNavbar from '@/Components/TopNavbar';

interface AuthenticatedLayoutProps {
    children: ReactNode;
}

export default function AuthenticatedLayout({
    children,
}: AuthenticatedLayoutProps) {
    return (
        <div className="h-screen overflow-hidden bg-white">
            <TopNavbar />

            <div className="flex h-[calc(100vh-64px)]">
                <Sidebar />

                <main className="flex-1 overflow-y-auto bg-[#F7F8FA]">
                    {children}
                </main>
            </div>
        </div>
    );
}