import {
    ChevronDown,
    ChevronRight,
} from 'lucide-react';

import InquiryDetailTable from './InquiryDetailTable';

import type {
    Inquiry,
} from '../../types/inquiry';

/*
|--------------------------------------------------------------------------
| Option Types
|--------------------------------------------------------------------------
*/

interface ProductOption {
    id: number;
    name: string;
    code: string;
    stdPrice: number | null;
}

interface GradeOption {
    id: number;
    name: string;
}

interface CompetitorOption {
    id: number;
    name: string;
    divisi?: string | null;
}

interface CompetitorPrice {
    id: number;

    competitorId: number;
    competitorName: string;
    productId: number;

    price: number | null;

    date: string | null;

    note?: string | null; 
}

/*
|--------------------------------------------------------------------------
| Props
|--------------------------------------------------------------------------
*/

interface Props {
    inquiry: Inquiry;

    open: boolean;

    onToggle: () => void;
 
    products: ProductOption[];

    grades: GradeOption[];

    competitorPrices: CompetitorPrice[];
}

/*
|--------------------------------------------------------------------------
| Helper
|--------------------------------------------------------------------------
*/

function formatPrice(
    value: number | null
) {
    if (
        value === null ||
        value === undefined
    ) {
        return '-';
    }

    return new Intl.NumberFormat(
        'id-ID',
        {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }
    ).format(value);
}

/*
|--------------------------------------------------------------------------
| Component
|--------------------------------------------------------------------------
*/

export default function InquiryAccordion({
    inquiry,

    open,

    onToggle,

    products,

    grades,

    competitorPrices,
}: Props) {
    return (
        <>
            {/* MAIN ROW */}
            <tr
                onClick={
                    onToggle
                }
                className="
                    cursor-pointer
                    border-b
                    border-gray-100
                    transition
                    hover:bg-gray-50
                "
            >
                {/* EXPAND */}
                <td className="px-4 py-4">
                    {open ? (
                        <ChevronDown
                            size={
                                17
                            }
                        />
                    ) : (
                        <ChevronRight
                            size={
                                17
                            }
                        />
                    )}
                </td>

                {/* CODE */}
                <td className="px-4 py-4 font-semibold text-gray-900">
                    {
                        inquiry.code
                    }
                </td>

                {/* DATE */}
                <td className="whitespace-nowrap px-4 py-4 text-gray-600">
                    {
                        inquiry.date
                    }
                </td>

                {/* ETD */}
                <td className="whitespace-nowrap px-4 py-4 text-gray-600">
                    {
                        inquiry.etd ??
                        '-'
                    }
                </td>

                {/* PIC SALES */}
                <td className="px-4 py-4">
                    {
                        inquiry
                            .picUser
                            ?.name ??
                        '-'
                    }
                </td>

                {/* CUSTOMER */}
                <td className="px-4 py-4 font-medium text-gray-800">
                    {
                        inquiry
                            .customer
                            ?.name ??
                        '-'
                    }
                </td>

                {/* SHIPPING RATE */}
                <td className="whitespace-nowrap px-4 py-4 text-gray-600">
                    {formatPrice(
                        inquiry.shippingRate
                    )}
                </td>

                {/* NOTE */}
                <td className="max-w-[260px] px-4 py-4 text-gray-600">
                    <div
                        className="
                            truncate
                        "
                        title={
                            inquiry.note ??
                            ''
                        }
                    >
                        {
                            inquiry.note ??
                            '-'
                        }
                    </div>
                </td>

                {/* DETAIL COUNT */}
                <td className="px-4 py-4 text-center">
                    <span
                        className="
                            inline-flex
                            min-w-[28px]
                            items-center
                            justify-center
                            rounded-full
                            bg-emerald-50
                            px-2
                            py-1
                            text-xs
                            font-semibold
                            text-emerald-700
                        "
                    >
                        {
                            inquiry
                                .details
                                .length
                        }
                    </span>
                </td>
            </tr>

            {/* DETAIL ROW */}
            {open && (
                <tr>
                    <td
                        colSpan={
                            9
                        }
                        className="
                            bg-[#f8faf9]
                            p-0
                        "
                    >
                        <div
                            className="
                                border-b
                                border-gray-200
                                p-5
                            "
                        >
                            <InquiryDetailTable
                                inquiry={
                                    inquiry
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
                    </td>
                </tr>
            )}
        </>
    );
}