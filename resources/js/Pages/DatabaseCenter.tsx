import {
    Head,
    router,
} from '@inertiajs/react';

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
                        />

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