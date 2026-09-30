import {
    MapPin,
    Pencil,
    Trash2,
} from 'lucide-react';

import type {
    Customer,
    Division,
    RiskLevel,
} from '../../types/databaseCenter';

interface Props {
    data: Customer[];
}

function riskColor(level: RiskLevel) {
    switch (level) {
        case 'Low Risk':
            return 'text-emerald-600';

        case 'Medium Risk':
            return 'text-amber-600';

        case 'High Risk':
            return 'text-red-600';
    }
}

function divisionClass(
    division: Division
) {
    switch (division) {
        case 'INDUSTRY':
            return 'bg-purple-100 text-purple-700';

        case 'SME':
            return 'bg-blue-100 text-blue-700';

        case 'LOW COST':
            return 'bg-amber-100 text-amber-700';
    }
}

export default function CustomerTable({
    data,
}: Props) {
    return (
        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="w-full min-w-[1100px] text-left text-sm">
                    <thead>
                        <tr className="border-b border-gray-200 text-gray-700">
                            <th className="px-5 py-4">
                                No
                            </th>

                            <th className="px-5 py-4">
                                Company
                            </th>

                            <th className="px-5 py-4">
                                Address
                            </th>

                            <th className="px-5 py-4">
                                Segmentation
                            </th>

                            <th className="px-5 py-4">
                                Level
                            </th>

                            <th className="px-5 py-4">
                                Divisi
                            </th>

                            <th className="px-5 py-4">
                                Kontak PIC
                            </th>

                            <th className="px-5 py-4">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        {data.map(
                            (customer, index) => (
                                <tr
                                    key={
                                        customer.id
                                    }
                                    className="border-b border-gray-100 last:border-0"
                                >
                                    <td className="px-5 py-4 text-gray-500">
                                        {index + 1}
                                    </td>

                                    <td className="px-5 py-4">
                                        <div className="font-semibold text-gray-900">
                                            {
                                                customer.company
                                            }
                                        </div>

                                        <div className="mt-1 text-xs text-gray-500">
                                            ID:{' '}
                                            {
                                                customer.customerId
                                            }
                                        </div>
                                    </td>

                                    <td className="px-5 py-4">
                                        <div className="flex items-start gap-2 text-gray-700">
                                            <MapPin
                                                size={
                                                    15
                                                }
                                                className="mt-0.5 shrink-0 text-gray-400"
                                            />

                                            {
                                                customer.address
                                            }
                                        </div>
                                    </td>

                                    <td className="px-5 py-4">
                                        {
                                            customer.segmentation
                                        }
                                    </td>

                                    <td
                                        className={`
                                            px-5 py-4
                                            font-semibold
                                            ${riskColor(
                                                customer.level
                                            )}
                                        `}
                                    >
                                        {
                                            customer.level
                                        }
                                    </td>

                                    <td className="px-5 py-4">
                                        <span
                                            className={`
                                                rounded-md
                                                px-2 py-1
                                                text-xs
                                                font-semibold
                                                ${divisionClass(
                                                    customer.division
                                                )}
                                            `}
                                        >
                                            {
                                                customer.division
                                            }
                                        </span>
                                    </td>

                                    <td className="px-5 py-4">
                                        <div>
                                            {
                                                customer.pic
                                            }
                                        </div>

                                        <div className="text-xs text-gray-500">
                                            {
                                                customer.phone
                                            }
                                        </div>
                                    </td>

                                    <td className="px-5 py-4">
                                        <div className="flex gap-3">
                                            <button>
                                                <Pencil
                                                    size={
                                                        16
                                                    }
                                                />
                                            </button>

                                            <button>
                                                <Trash2
                                                    size={
                                                        16
                                                    }
                                                />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            )
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}