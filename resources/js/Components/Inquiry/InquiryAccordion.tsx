import {
    ChevronDown,
    ChevronRight,
    FileText,
    Sheet,
    SquarePen,
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

    onEdit: (inquiry: Inquiry) => void;

    onExcel: (inquiryId: number) => void;

    onPdf: (inquiry: Inquiry) => void;
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
    onEdit,
    onExcel,
    onPdf,
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
                <td className="px-2 py-2">
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
                <td className="px-2 py-2 font-semibold text-gray-900">
                    {
                        inquiry.code
                    }
                </td>

                {/* DATE */}
                <td className="whitespace-nowrap px-2 py-2 text-gray-600">
                    {
                        inquiry.date
                    }
                </td>

                {/* ETD */}
                <td className="whitespace-nowrap px-2 py-2 text-gray-600">
                    {
                        inquiry.etd ??
                        '-'
                    }
                </td>

                {/* PIC SALES */}
                <td className="px-2 py-2">
                    {
                        inquiry
                            .picUser
                            ?.name ??
                        '-'
                    }
                </td>

                {/* CUSTOMER */}
                <td className="px-2 py-2 font-medium text-gray-800">
                    {
                        inquiry
                            .customer
                            ?.name ??
                        '-'
                    }
                </td>

                {/* SHIPPING RATE */}
                <td className="whitespace-nowrap px-2 py-2 text-gray-600">
                    {formatPrice(
                        inquiry.shippingRate
                    )}
                </td>

                {/* NOTE */}
                <td className="max-w-[260px] px-2 py-2 text-gray-600">
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
                <td className="px-2 py-2 text-center">
                    <span className="inline-flex min-w-[28px] items-center justify-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">
                        {
                            inquiry
                                .details
                                .length
                        }
                    </span>
                </td>

                <td className="px-2 py-2 text-center">
                    <div className="flex">
                        <button
                            type="button"
                            title="Edit"
                            aria-label={`Edit inquiry ${inquiry.code}`}
                            onClick={(event) => {
                                event.stopPropagation();
                                onEdit(inquiry);
                            }}
                            className="inline-flex min-w-[28px] items-center justify-center rounded-full bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700"
                        >
                            <SquarePen size={16} />
                        </button>
                        <button
                            type="button"
                            title="Print Excel"
                            aria-label={`Download Excel inquiry ${inquiry.code}`}
                            onClick={(event) => {
                                event.stopPropagation();
                                onExcel(inquiry.id);
                            }}
                            className="inline-flex min-w-[28px] items-center justify-center rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700"
                        >
                            <Sheet size={16} />
                        </button>
                        <button
                            type="button"
                            title="Print PDF"
                            aria-label={`Download PDF inquiry ${inquiry.code}`}
                            onClick={(event) => {
                                event.stopPropagation();
                                onPdf(inquiry);
                            }}
                            className="inline-flex min-w-[28px] items-center justify-center rounded-full bg-red-50 px-2 py-1 text-xs font-semibold text-red-700"
                        >
                            <FileText size={16}  />
                        </button>
                    </div>
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