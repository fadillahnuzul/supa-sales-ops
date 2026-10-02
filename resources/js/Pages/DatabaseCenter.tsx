import {
    Head,
    router,
} from '@inertiajs/react';

import { route } from 'ziggy-js';

import {
    useEffect,
    useMemo,
    useRef,
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

/*
 * Product tidak lagi diambil
 * dari dummy data.
 */
import {
    competitors,
} from '../data/databaseCenter';

import type {
    Customer,
    DatabaseTab,
    Product,
} from '../types/databaseCenter';


interface SegmentationOption {
    id: number;
    name: string;
}

interface MaterialOption {
    id: number;
    name: string;

    code?: string | null;

    source_type:
    | 'material'
    | 'product';

    source_key: string;
}

interface GradeOption {
    id: number;
    name: string;
}

interface DatabaseCenterProps {
    customers: Customer[];

    products: Product[];

    segmentations:
    SegmentationOption[];

    materials:
    MaterialOption[];

    grades:
    GradeOption[];
}


/*
 * ================================
 * COMPONENT
 * ================================
 */

export default function DatabaseCenter({
    customers: initialCustomers,
    products = [],
    segmentations = [],
    materials = [],
    grades = [],
}: DatabaseCenterProps) {
    /*
     * ============================
     * TAB
     * ============================
     */

    const [
        activeTab,
        setActiveTab,
    ] = useState<DatabaseTab>(
        'customer'
    );


    /*
     * ============================
     * MODAL
     * ============================
     */

    const [
        modalOpen,
        setModalOpen,
    ] = useState(false);


    /*
     * ============================
     * EDITING CUSTOMER
     * ============================
     */

    const [
        editingCustomer,
        setEditingCustomer,
    ] = useState<Customer | null>(
        null
    );


    /*
     * ============================
     * EDITING PRODUCT
     * ============================
     */

    const [
        editingProduct,
        setEditingProduct,
    ] = useState<Product | null>(
        null
    );


    /*
     * ============================
     * SEARCH
     * ============================
     */

    const [
        search,
        setSearch,
    ] = useState('');


    /*
     * ============================
     * CUSTOMER LOCAL ROWS
     * ============================
     */

    const [
        customerRows,
        setCustomerRows,
    ] = useState<Customer[]>(
        initialCustomers
    );


    /*
     * ============================
     * IMPORT
     * ============================
     */

    const fileInputRef =
        useRef<HTMLInputElement | null>(
            null
        );

    const [
        importFeedback,
        setImportFeedback,
    ] = useState<{
        type:
        | 'info'
        | 'success'
        | 'error';

        message: string;
    } | null>(
        null
    );


    /*
     * Sync customer props.
     */
    useEffect(() => {
        setCustomerRows(
            initialCustomers
        );
    }, [
        initialCustomers,
    ]);


    /*
     * ============================
     * FILTER CUSTOMER
     * ============================
     */

    const filteredCustomers =
        useMemo(() => {
            const keyword =
                search
                    .trim()
                    .toLowerCase();

            if (!keyword) {
                return customerRows;
            }

            return customerRows.filter(
                (customer) =>
                    (customer.company ?? '')
                        .toLowerCase()
                        .includes(
                            keyword
                        ) ||

                    (customer.address ?? '')
                        .toLowerCase()
                        .includes(
                            keyword
                        ) ||

                    (customer.pic ?? '')
                        .toLowerCase()
                        .includes(
                            keyword
                        )
            );
        }, [
            customerRows,
            search,
        ]);


    /*
     * ============================
     * FILTER PRODUCT
     * ============================
     */

    const filteredProducts =
        useMemo(() => {
            const keyword =
                search
                    .trim()
                    .toLowerCase();

            if (!keyword) {
                return products;
            }

            return products.filter(
                (product) =>
                    (product.name ?? '')
                        .toLowerCase()
                        .includes(keyword) ||

                    (product.code ?? '')
                        .toLowerCase()
                        .includes(keyword)
            );
        }, [
            products,
            search,
        ]);


    /*
     * ============================
     * FILTER COMPETITOR
     * ============================
     */

    const filteredCompetitors =
        useMemo(() => {
            const keyword =
                search
                    .trim()
                    .toLowerCase();

            if (!keyword) {
                return competitors;
            }

            return competitors.filter(
                (competitor) =>
                    (competitor.competitor ?? '')
                        .toLowerCase()
                        .includes(
                            keyword
                        ) ||

                    (competitor.product ?? '')
                        .toLowerCase()
                        .includes(
                            keyword
                        )
            );
        }, [
            search,
        ]);


    /*
     * ============================
     * CHANGE TAB
     * ============================
     */

    function changeTab(
        tab: DatabaseTab
    ) {
        setActiveTab(tab);

        setSearch('');

        /*
         * Bersihkan edit state
         * ketika pindah tab.
         */
        setEditingCustomer(null);

        setEditingProduct(null);
    }


    /*
     * ============================
     * CREATE
     * ============================
     */

    function openCreateModal() {
        setEditingCustomer(null);

        setEditingProduct(null);

        setModalOpen(true);
    }


    /*
     * ============================
     * EDIT CUSTOMER
     * ============================
     */

    function openEditCustomerModal(
        customer: Customer
    ) {
        setEditingProduct(null);

        setEditingCustomer(
            customer
        );

        setModalOpen(true);
    }


    /*
     * ============================
     * EDIT PRODUCT
     * ============================
     */

    function openEditProductModal(
        product: Product
    ) {
        setEditingCustomer(null);

        setEditingProduct(
            product
        );

        setModalOpen(true);
    }


    /*
     * ============================
     * CLOSE MODAL
     * ============================
     */

    function closeModal() {
        setModalOpen(false);

        setEditingCustomer(null);

        setEditingProduct(null);
    }


    function handleDownloadTemplate() {
        if (
            activeTab !== 'customer' &&
            activeTab !== 'product'
        ) {
            return;
        }

        window.open(
            route(
                activeTab === 'product'
                    ? 'products.template'
                    : 'customers.template'
            ),
            '_blank'
        );
    }

    function handleImportClick() {
        if (
            activeTab !== 'customer' &&
            activeTab !== 'product'
        ) {
            return;
        }

        setImportFeedback(null);

        fileInputRef.current
            ?.click();
    }


    function handleImportFileChange(
        event:
            React.ChangeEvent<HTMLInputElement>
    ) {
        const file =
            event.target.files?.[0];

        if (!file) {
            return;
        }

        const formData =
            new FormData();

        const importTarget =
            activeTab === 'product'
                ? 'produk'
                : 'customer';

        formData.append(
            'file',
            file
        );

        setImportFeedback({
            type: 'info',
            message:
                `Sedang mengimpor data ${importTarget}...`,
        });

        router.post(
            route(
                activeTab === 'product'
                    ? 'products.import'
                    : 'customers.import'
            ),

            formData,

            {
                forceFormData: true,

                preserveScroll: true,

                onSuccess: () => {
                    setImportFeedback({
                        type: 'success',

                        message:
                            `Data ${importTarget} berhasil diimpor.`,
                    });
                },

                onError: (
                    errors
                ) => {
                    const message =
                        errors.file ??
                        Object.values(
                            errors
                        )[0];

                    setImportFeedback({
                        type: 'error',

                        message: String(
                            message ??
                            'Import gagal. Periksa file dan coba lagi.'
                        ),
                    });
                },

                onFinish: () => {
                    if (
                        fileInputRef.current
                    ) {
                        fileInputRef.current.value =
                            '';
                    }
                },
            }
        );
    }


    /*
     * ============================
     * DELETE CUSTOMER
     * ============================
     */

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
            route(
                'customers.destroy',
                customerId
            ),

            {
                preserveScroll: true,

                onSuccess: () => {
                    setCustomerRows(
                        (
                            currentRows
                        ) =>
                            currentRows.filter(
                                (
                                    customer
                                ) =>
                                    customer.id !==
                                    customerId
                            )
                    );
                },
            }
        );
    }


    /*
     * ============================
     * DELETE PRODUCT
     * ============================
     */

    function handleProductDelete(
        productId: number
    ) {
        if (
            !window.confirm(
                'Hapus product ini?'
            )
        ) {
            return;
        }

        router.delete(
            route(
                'products.destroy',
                productId
            ),
            {
                preserveScroll: true,

                onError: (
                    errors
                ) => {
                    const message =
                        errors.product ??
                        Object.values(
                            errors
                        )[0];

                    if (message) {
                        window.alert(
                            String(
                                message
                            )
                        );
                    }
                },
            }
        );
    }


    return (
        <>
            <Head title="Database Center" />

            <AuthenticatedLayout>
                <div className="min-h-full bg-[#f6f7f8] px-4 py-3">
                    <div className="mx-auto space-y-5">

                        {/* HEADER */}

                        <DatabaseHeader
                            activeTab={
                                activeTab
                            }

                            onAdd={
                                openCreateModal
                            }

                            onImport={
                                handleImportClick
                            }

                            onDownloadTemplate={
                                handleDownloadTemplate
                            }
                        />


                        {/* HIDDEN CUSTOMER/PRODUCT IMPORT */}

                        {(activeTab === 'customer' ||
                            activeTab === 'product') && (
                                <input
                                    ref={
                                        fileInputRef
                                    }

                                    type="file"

                                    accept=".csv,.xlsx"

                                    className="hidden"

                                    onChange={
                                        handleImportFileChange
                                    }
                                />
                            )}


                        {/* IMPORT FEEDBACK */}

                        {importFeedback && (
                            <div
                                role={
                                    importFeedback.type ===
                                        'error'
                                        ? 'alert'
                                        : 'status'
                                }

                                className={`
                                    rounded-lg
                                    border
                                    px-4
                                    py-3
                                    text-sm

                                    ${importFeedback.type ===
                                        'error'
                                        ? 'border-red-200 bg-red-50 text-red-700'
                                        : importFeedback.type ===
                                            'success'
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border-gray-200 bg-white text-gray-700'
                                    }
                                `}
                            >
                                {
                                    importFeedback.message
                                }
                            </div>
                        )}


                        {/* TABS */}

                        <DatabaseTabs
                            activeTab={
                                activeTab
                            }

                            onChange={
                                changeTab
                            }
                        />


                        {/* SEARCH */}

                        <DatabaseToolbar
                            activeTab={
                                activeTab
                            }

                            search={
                                search
                            }

                            setSearch={
                                setSearch
                            }
                        />


                        {/* CUSTOMER */}

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


                        {/* PRODUCT */}

                        {activeTab ===
                            'product' && (
                                <ProductTable
                                    data={
                                        filteredProducts
                                    }

                                    onEdit={
                                        openEditProductModal
                                    }

                                    onDelete={
                                        handleProductDelete
                                    }
                                />
                            )}


                        {/* COMPETITOR */}

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


                {/* FORM MODAL */}

                <DatabaseFormModal
                    open={
                        modalOpen
                    }

                    activeTab={
                        activeTab
                    }

                    onClose={
                        closeModal
                    }

                    editingCustomer={
                        editingCustomer
                    }

                    editingProduct={
                        editingProduct
                    }

                    segmentations={
                        segmentations
                    }

                    materials={
                        materials
                    }

                    products={
                        products
                    }

                    grades={
                        grades
                    }
                />
            </AuthenticatedLayout>
        </>
    );
}