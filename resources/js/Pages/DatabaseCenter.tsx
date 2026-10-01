import {
    Head,
    router,
} from '@inertiajs/react';

import {
    useRef,
} from 'react';

import { route } from 'ziggy-js';

import {
    useEffect,
    useMemo,
    useState,
} from 'react';

import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';

import DatabaseTabs from '../Components/DatabaseCenter/DatabaseTabs';
import DatabaseHeader from '../Components/DatabaseCenter/DatabaseHeader';
import DatabaseToolbar from '../Components/DatabaseCenter/DatabaseToolbar';
import DatabaseFormModal from '../Components/DatabaseCenter/DatabaseFormModal';

import CustomerTable from '../Components/DatabaseCenter/CustomerTable';
import ProductTable from '../Components/DatabaseCenter/ProductTable';
import CompetitorTable from '../Components/DatabaseCenter/CompetitorTable';

import {
    products,
    competitors,
} from '../data/databaseCenter';

import type {
    Customer,
    DatabaseTab,
} from '../types/databaseCenter';

interface DatabaseCenterProps {
    customers: Customer[];
    segmentations: Array<{
        id: number;
        name: string;
    }>;
}

export default function DatabaseCenter({
    customers: initialCustomers,
    segmentations,
}: DatabaseCenterProps) {
    const [
        activeTab,
        setActiveTab,
    ] = useState<DatabaseTab>(
        'customer'
    );

    const [modalOpen, setModalOpen] =
        useState(false);

    const [
        editingCustomer,
        setEditingCustomer,
    ] = useState<Customer | null>(
        null
    );

    const [
        search,
        setSearch,
    ] = useState('');

    const [customerRows, setCustomerRows] =
        useState<Customer[]>(initialCustomers);

    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const [importFeedback, setImportFeedback] = useState<{
        type: 'info' | 'success' | 'error';
        message: string;
    } | null>(null);

    useEffect(() => {
        setCustomerRows(initialCustomers);
    }, [initialCustomers]);

    const filteredCustomers =
        useMemo(() => {
            const keyword =
                search.toLowerCase();

            return customerRows.filter(
                (customer) =>
                    customer.company
                        .toLowerCase()
                        .includes(keyword) ||
                    customer.address
                        .toLowerCase()
                        .includes(keyword) ||
                    customer.pic
                        .toLowerCase()
                        .includes(keyword)
            );
        }, [customerRows, search]);

    const filteredProducts =
        useMemo(() => {
            const keyword =
                search.toLowerCase();

            return products.filter(
                (product) =>
                    product.name
                        .toLowerCase()
                        .includes(keyword) ||
                    product.itemCode
                        .toLowerCase()
                        .includes(keyword)
            );
        }, [search]);

    const filteredCompetitors =
        useMemo(() => {
            const keyword =
                search.toLowerCase();

            return competitors.filter(
                (competitor) =>
                    competitor.competitor
                        .toLowerCase()
                        .includes(keyword) ||
                    competitor.product
                        .toLowerCase()
                        .includes(keyword)
            );
        }, [search]);

    function changeTab(
        tab: DatabaseTab
    ) {
        setActiveTab(tab);
        setSearch('');
    }

    function openCreateCustomerModal() {
        setEditingCustomer(null);
        setModalOpen(true);
    }

    function openEditCustomerModal(
        customer: Customer
    ) {
        setEditingCustomer(customer);
        setModalOpen(true);
    }

    function closeCustomerModal() {
        setModalOpen(false);
        setEditingCustomer(null);
    }

    function handleDownloadTemplate() {
        window.open(
            route('customers.template'),
            '_blank'
        );
    }

    function handleImportClick() {
        setImportFeedback(null);
        fileInputRef.current?.click();
    }

    function handleImportFileChange(
        event: React.ChangeEvent<HTMLInputElement>
    ) {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        setImportFeedback({
            type: 'info',
            message: 'Sedang mengimpor data customer...',
        });

        router.post(
            route('customers.import'),
            formData,
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setImportFeedback({
                        type: 'success',
                        message: 'Data customer berhasil diimpor.',
                    });
                },
                onError: (errors) => {
                    const message = errors.file ?? Object.values(errors)[0];
                    setImportFeedback({
                        type: 'error',
                        message: String(message ?? 'Import gagal. Periksa file dan coba lagi.'),
                    });
                },
                onFinish: () => {
                    if (fileInputRef.current) {
                        fileInputRef.current.value = '';
                    }
                },
            }
        );
    }

    function handleCustomerDelete(
        customerId: number
    ) {
        if (
            !window.confirm(
                'Hapus customer ini?'
            )
        ) {
            return;
        }

        router.delete(
            route('customers.destroy', customerId),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setCustomerRows((
                        currentRows
                    ) =>
                        currentRows.filter(
                            (customer) =>
                                customer.id !==
                                customerId
                        )
                    );
                },
            }
        );
    }

    return (
        <>
            <Head title="Database Center" />

            <AuthenticatedLayout>
                <div className="min-h-full bg-[#f6f7f8] py-3 px-4">
                    <div className="mx-auto space-y-5">
                        <DatabaseHeader
                            activeTab={
                                activeTab
                            }
                            onAdd={
                                openCreateCustomerModal
                            }
                            onImport={
                                handleImportClick
                            }
                            onDownloadTemplate={
                                handleDownloadTemplate
                            }
                        />

                        {activeTab === 'customer' && (
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".csv,.xlsx"
                                className="hidden"
                                onChange={
                                    handleImportFileChange
                                }
                            />
                        )}

                        {importFeedback && (
                            <div
                                role={importFeedback.type === 'error' ? 'alert' : 'status'}
                                className={`rounded-lg border px-4 py-3 text-sm ${
                                    importFeedback.type === 'error'
                                        ? 'border-red-200 bg-red-50 text-red-700'
                                        : importFeedback.type === 'success'
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border-gray-200 bg-white text-gray-700'
                                }`}
                            >
                                {importFeedback.message}
                            </div>
                        )}

                        <DatabaseTabs
                            activeTab={
                                activeTab
                            }
                            onChange={
                                changeTab
                            }
                        />

                        <DatabaseToolbar
                            activeTab={
                                activeTab
                            }
                            search={search}
                            setSearch={
                                setSearch
                            }
                        />

                        {activeTab ===
                            'customer' && (
                                <CustomerTable
                                    data={
                                        filteredCustomers
                                    }
                                    onEdit={
                                        openEditCustomerModal
                                    }
                                    onDelete={
                                        handleCustomerDelete
                                    }
                                />
                            )}

                        {activeTab ===
                            'product' && (
                                <ProductTable
                                    data={
                                        filteredProducts
                                    }
                                />
                            )}

                        {activeTab ===
                            'competitor' && (
                                <CompetitorTable
                                    data={
                                        filteredCompetitors
                                    }
                                />
                            )}
                    </div>
                </div>

                <DatabaseFormModal
                    open={modalOpen}
                    activeTab={activeTab}
                    onClose={closeCustomerModal}
                    editingCustomer={
                        editingCustomer
                    }
                    segmentations={
                        segmentations
                    }
                />
            </AuthenticatedLayout>
        </>
    );
}