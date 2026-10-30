import { Head, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import {
    useMemo,
    useState,
} from 'react';

import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';

import InquiryHeader from '../Components/Inquiry/InquiryHeader';
import InquiryTable from '../Components/Inquiry/InquiryTable';
import InquiryFormModal from '../Components/Inquiry/InquiryFormModal';
import {
    downloadInquiryExcel,
    downloadInquiryPdf,
    fetchPrintTemplates,
    fetchSalesSignatures,
} from '../Services/InquiryPrintApi';

import type {
    Inquiry as InquiryType,
} from '../types/inquiry';
import type { PageProps } from '../types';

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
    const { auth } = usePage<PageProps>().props;
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
    const [editingInquiry, setEditingInquiry] = useState<InquiryType | null>(null);

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

    async function handleDownloadExcel(inquiryId: number) {
        try {
            await downloadInquiryExcel(inquiryId);
        } catch (error) {
            console.error('Download inquiry Excel failed:', error);
            window.alert('Gagal mengunduh file Excel inquiry. Silakan coba lagi.');
        }
    }

    async function handleDownloadPdf(inquiry: InquiryType) {
        try {
            const [templates, signatures] = await Promise.all([
                fetchPrintTemplates(),
                fetchSalesSignatures(),
            ]);
            const template = templates.find((item) => item.is_default) ?? templates[0];

            if (!template) {
                window.alert('Template quotation belum tersedia. Tambahkan template di halaman Print terlebih dahulu.');
                return;
            }

            const signature = signatures.find(
                (item) => inquiry.pic !== null && Number(item.user_id) === Number(inquiry.pic)
            );
            const quotationDate = new Date().toISOString().slice(0, 10);

            await downloadInquiryPdf(inquiry.id, {
                template_id: template.id,
                signature_id: signature?.id ?? null,
                signature_user_id: inquiry.pic,
                quotation_date: quotationDate,
            });
        } catch (error) {
            console.error('Download inquiry PDF failed:', error);
            window.alert('Gagal membuat PDF inquiry. Periksa konfigurasi template di halaman Print dan coba lagi.');
        }
    }

    function openCreateModal() {
        setEditingInquiry(null);
        setInquiryModal(true);
    }

    function openEditModal(inquiry: InquiryType) {
        setEditingInquiry(inquiry);
        setInquiryModal(true);
    }

    return (
        <>
            <Head title="Inquiry" />

            <AuthenticatedLayout>
                <div className="min-h-full bg-[#f6f7f8] p-6">
                    <div className="mx-auto max-w-[1700px] space-y-5">

                        {/* HEADER */}
                        <InquiryHeader
                            onAdd={openCreateModal}
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

                            onEdit={openEditModal}

                            onExcel={handleDownloadExcel}

                            onPdf={handleDownloadPdf}
                        />

                    </div>
                </div>

                {/* ADD INQUIRY */}
                <InquiryFormModal
                    open={
                        inquiryModal
                    }

                    inquiry={editingInquiry}

                    customers={
                        customers
                    }

                    pics={
                        pics
                    }

                    defaultPicId={auth.user?.id}

                    onClose={() => {
                        setInquiryModal(false);
                        setEditingInquiry(null);
                    }}
                />
            </AuthenticatedLayout>
        </>
    );
}