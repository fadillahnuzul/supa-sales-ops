import { Head } from '@inertiajs/react';
import { Search } from 'lucide-react';
import {
    useMemo,
    useState,
} from 'react';

import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';

import InquiryHeader from '../Components/Inquiry/InquiryHeader';
import InquiryTable from '../Components/Inquiry/InquiryTable';
import InquiryFormModal from '../Components/Inquiry/InquiryFormModal';

import type {
    Inquiry as InquiryType,
} from '../types/inquiry';

export interface CustomerOption {
    id: number;
    name: string;
    division?: string | null;
    riskLevel?: string | null;
}

export interface PicOption {
    id: number;
    name: string;
}

export interface ProductOption {
    id: number;
    name: string;
    code: string;
    stdPrice: number | null;
}

export interface GradeOption {
    id: number;
    name: string;
}

export interface CompetitorPrice {
    id: number;
    competitorId: number;
    competitorName: string;
    productId: number;
    price: number | null;
    date: string | null;
    note?: string | null;
}

interface Props {
    inquiries: InquiryType[];

    customers: CustomerOption[];
    pics: PicOption[];

    products: ProductOption[];
    grades: GradeOption[];

    competitorPrices: CompetitorPrice[];
}

export default function Inquiry({
    inquiries,
    customers,
    pics,
    products,
    grades,
    competitorPrices,
}: Props) {
    const [
        search,
        setSearch,
    ] = useState('');

    const [
        openedId,
        setOpenedId,
    ] = useState<number | null>(
        null
    );

    const [
        inquiryModal,
        setInquiryModal,
    ] = useState(false);

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    const filteredData =
        useMemo(() => {
            const keyword =
                search
                    .trim()
                    .toLowerCase();

            if (!keyword) {
                return inquiries;
            }

            return inquiries.filter(
                (inquiry) => {
                    const code =
                        inquiry.code ??
                        '';

                    const customer =
                        inquiry.customer
                            ?.name ??
                        '';

                    const pic =
                        inquiry.picUser
                            ?.name ??
                        '';

                    return (
                        code
                            .toLowerCase()
                            .includes(
                                keyword
                            ) ||

                        customer
                            .toLowerCase()
                            .includes(
                                keyword
                            ) ||

                        pic
                            .toLowerCase()
                            .includes(
                                keyword
                            )
                    );
                }
            );
        }, [
            search,
            inquiries,
        ]);

    /*
    |--------------------------------------------------------------------------
    | Accordion
    |--------------------------------------------------------------------------
    */

    function toggleRow(
        id: number
    ) {
        setOpenedId(
            openedId === id
                ? null
                : id
        );
    }

    return (
        <>
            <Head title="Inquiry" />

            <AuthenticatedLayout>
                <div className="min-h-full bg-[#f6f7f8] p-6">
                    <div className="mx-auto max-w-[1700px] space-y-5">

                        {/* HEADER */}
                        <InquiryHeader
                            onAdd={() =>
                                setInquiryModal(
                                    true
                                )
                            }
                        />

                        {/* SEARCH */}
                        <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div className="relative max-w-xl">

                                <Search
                                    size={17}
                                    className="
                                        absolute
                                        left-4
                                        top-1/2
                                        -translate-y-1/2
                                        text-gray-400
                                    "
                                />

                                <input
                                    value={search}

                                    onChange={(
                                        event
                                    ) =>
                                        setSearch(
                                            event
                                                .target
                                                .value
                                        )
                                    }

                                    placeholder="Cari inquiry code, customer, atau PIC..."

                                    className="
                                        h-11
                                        w-full
                                        rounded-lg
                                        border
                                        border-gray-300
                                        pl-11
                                        pr-4
                                        text-sm
                                        outline-none
                                        transition

                                        focus:border-[#19875f]
                                        focus:ring-2
                                        focus:ring-[#19875f]/10
                                    "
                                />
                            </div>
                        </div>

                        {/* TABLE */}
                        <InquiryTable
                            data={
                                filteredData
                            }

                            openedId={
                                openedId
                            }

                            onToggle={
                                toggleRow
                            }

                            products={
                                products
                            }

                            grades={
                                grades
                            }

                            competitorPrices={
                                competitorPrices
                            }
                        />

                    </div>
                </div>

                {/* ADD INQUIRY */}
                <InquiryFormModal
                    open={
                        inquiryModal
                    }

                    customers={
                        customers
                    }

                    pics={
                        pics
                    }

                    onClose={() =>
                        setInquiryModal(
                            false
                        )
                    }
                />
            </AuthenticatedLayout>
        </>
    );
}