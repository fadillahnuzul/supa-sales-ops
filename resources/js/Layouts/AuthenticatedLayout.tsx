import { useState, type ReactNode } from 'react';

import Sidebar from '@/Components/Sidebar';
import TopNavbar from '@/Components/TopNavbar';

interface AuthenticatedLayoutProps {
    children: ReactNode;
}

export default function AuthenticatedLayout({
    children,
}: AuthenticatedLayoutProps) {
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);

    return (
        <div className="h-screen overflow-hidden bg-white">
            <TopNavbar
                isSidebarOpen={isSidebarOpen}
                onToggleSidebar={() => setIsSidebarOpen((isOpen) => !isOpen)}
            />

            <div className="flex h-[calc(100vh-64px)]">
                <div
                    id="app-sidebar"
                    className={`relative z-10 shrink-0 transition-[width] duration-300 ease-in-out ${
                        isSidebarOpen ? 'w-[200px]' : 'w-[80px]'
                    }`}
                >
                    <Sidebar
                        isOpen={isSidebarOpen}
                        onToggle={() => setIsSidebarOpen((isOpen) => !isOpen)}
                    />
                </div>

                <main className="min-w-0 flex-1 overflow-y-auto bg-[#F7F8FA]">
                    {children}
                </main>
            </div>
        </div>
    );
}