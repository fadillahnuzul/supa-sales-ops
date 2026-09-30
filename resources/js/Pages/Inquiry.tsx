import {
    Head,
} from '@inertiajs/react';

import {
    Search,
} from 'lucide-react';

import {
    useMemo,
    useState,
} from 'react';

import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';

import InquiryHeader from '../Components/Inquiry/InquiryHeader';
import InquiryTable from '../Components/Inquiry/InquiryTable';
import InquiryFormModal from '../Components/Inquiry/InquiryFormModal';

import {
    inquiryData,
} from '../data/inquiry';

export default function Inquiry() {
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


    const filteredData =
        useMemo(() => {
            const keyword =
                search.toLowerCase();

            return inquiryData.filter(
                (inquiry) =>
                    inquiry.inquiryCode
                        .toLowerCase()
                        .includes(keyword) ||
                    inquiry.customer
                        .toLowerCase()
                        .includes(keyword) ||
                    inquiry.picSales
                        .toLowerCase()
                        .includes(keyword)
            );
        }, [search]);

    function toggleRow(id: number) {
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
                        <InquiryHeader
                            onAdd={() =>
                                setInquiryModal(
                                    true
                                )
                            }
                        />

                        <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <div className="relative max-w-xl">
                                <Search
                                    size={17}
                                    className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"
                                />

                                <input
                                    value={search}
                                    onChange={(e) =>
                                        setSearch(
                                            e.target.value
                                        )
                                    }
                                    placeholder="Cari inquiry code, customer, atau PIC Sales..."
                                    className="
                                        h-11 w-full
                                        rounded-lg
                                        border border-gray-300
                                        pl-11 pr-4
                                        text-sm
                                        outline-none
                                        focus:border-[#19875f]
                                    "
                                />
                            </div>
                        </div>

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
                        />
                    </div>
                </div>

                <InquiryFormModal
                    open={inquiryModal}
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