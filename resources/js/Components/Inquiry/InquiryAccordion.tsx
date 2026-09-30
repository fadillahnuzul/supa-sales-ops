import {
    ChevronDown,
    ChevronRight,
} from 'lucide-react';

import InquiryDetailTable from './InquiryDetailTable';

import type { Inquiry } from '../../types/inquiry';

interface Props {
    inquiry: Inquiry;
    open: boolean;
    onToggle: () => void;
}

function riskClass(level: Inquiry['level']) {
    switch (level) {
        case 'Low Risk':
            return 'text-emerald-600';

        case 'Medium Risk':
            return 'text-amber-600';

        case 'High Risk':
            return 'text-red-600';
    }
}

export default function InquiryAccordion({
    inquiry,
    open,
    onToggle,
}: Props) {
    return (
        <>
            <tr
                onClick={onToggle}
                className="
                    cursor-pointer
                    border-b border-gray-100
                    transition
                    hover:bg-gray-50
                "
            >
                <td className="px-4 py-4">
                    {open ? (
                        <ChevronDown size={17} />
                    ) : (
                        <ChevronRight size={17} />
                    )}
                </td>

                <td className="px-4 py-4 font-semibold text-gray-900">
                    {inquiry.inquiryCode}
                </td>

                <td className="px-4 py-4">
                    {inquiry.inquiryDate}
                </td>

                <td className="px-4 py-4">
                    {inquiry.etd}
                </td>

                <td className="px-4 py-4">
                    {inquiry.picSales}
                </td>

                <td className="px-4 py-4 font-medium">
                    {inquiry.customer}
                </td>

                <td className="px-4 py-4">
                    {inquiry.segmentation}
                </td>

                <td
                    className={`
                        px-4 py-4 font-semibold
                        ${riskClass(inquiry.level)}
                    `}
                >
                    {inquiry.level}
                </td>

                <td className="px-4 py-4">
                    {inquiry.sterilization}
                </td>

                <td className="px-4 py-4 text-center">
                    {inquiry.details.length}
                </td>
            </tr>

            {open && (
                <tr>
                    <td
                        colSpan={10}
                        className="bg-[#f8faf9] p-0"
                    >
                        <div className="border-b border-gray-200 p-5">
                            <InquiryDetailTable
                                inquiry={inquiry}
                            />
                        </div>
                    </td>
                </tr>
            )}
        </>
    );
}