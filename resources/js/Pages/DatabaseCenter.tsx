import {
    Head,
} from '@inertiajs/react';

import {
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
    customers,
    products,
    competitors,
} from '../data/databaseCenter';

import type {
    DatabaseTab,
} from '../types/databaseCenter';

export default function DatabaseCenter() {
    const [
        activeTab,
        setActiveTab,
    ] = useState<DatabaseTab>(
        'customer'
    );

    const [modalOpen, setModalOpen] = useState(false);

    const [
        search,
        setSearch,
    ] = useState('');

    const filteredCustomers =
        useMemo(() => {
            const keyword =
                search.toLowerCase();

            return customers.filter(
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
        }, [search]);

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
                            onAdd={() => setModalOpen(true)}
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
                    onClose={() => setModalOpen(false)}
                />
            </AuthenticatedLayout>
        </>
    );
}