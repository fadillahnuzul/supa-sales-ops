import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />

            <AuthenticatedLayout>
                <div className="p-2">
                    <div className="rounded-xl border border-gray-200 bg-white p-6">
                        <h1 className="text-2xl font-bold text-gray-900">
                            Dashboard
                        </h1>

                        <p className="mt-2 text-sm text-gray-500">
                            Welcome to Sales, Pricing & Quotation System.
                        </p>
                    </div>
                </div>
            </AuthenticatedLayout>
        </>
    );
}