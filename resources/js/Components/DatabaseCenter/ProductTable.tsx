import {
    Pencil,
    Trash2,
} from 'lucide-react';

import type {
    Product,
} from '../../types/databaseCenter';

interface Props {
    data: Product[];
}

function rupiah(value: number) {
    return new Intl.NumberFormat(
        'id-ID',
        {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }
    ).format(value);
}

export default function ProductTable({
    data,
}: Props) {
    return (
        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="w-full min-w-[1100px] text-left text-sm">
                    <thead>
                        <tr className="border-b border-gray-200">
                            <th className="px-5 py-4">
                                No
                            </th>

                            <th className="px-5 py-4">
                                Product Name
                            </th>

                            <th className="px-5 py-4">
                                Item Code
                            </th>

                            <th className="px-5 py-4">
                                Kategori
                            </th>

                            <th className="px-5 py-4">
                                Sterilisasi
                            </th>

                            <th className="px-5 py-4">
                                Standard Pricelist
                            </th>

                            <th className="px-5 py-4">
                                Satuan
                            </th>

                            <th className="px-5 py-4">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        {data.map(
                            (product, index) => (
                                <tr
                                    key={
                                        product.id
                                    }
                                    className="border-b border-gray-100 last:border-0"
                                >
                                    <td className="px-5 py-4 text-gray-500">
                                        {index + 1}
                                    </td>

                                    <td className="px-5 py-4">
                                        <div className="font-semibold">
                                            {
                                                product.name
                                            }
                                        </div>

                                        <div className="mt-1 text-xs text-gray-500">
                                            {
                                                product.description
                                            }
                                        </div>
                                    </td>

                                    <td className="px-5 py-4">
                                        <span className="rounded border border-gray-300 bg-gray-50 px-2 py-1 font-mono text-xs">
                                            {
                                                product.itemCode
                                            }
                                        </span>
                                    </td>

                                    <td className="px-5 py-4">
                                        {
                                            product.category
                                        }
                                    </td>

                                    <td className="px-5 py-4">
                                        <span className="rounded-md border border-emerald-300 bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">
                                            {
                                                product.sterilization
                                            }
                                        </span>
                                    </td>

                                    <td className="px-5 py-4 font-semibold">
                                        {rupiah(
                                            product.price
                                        )}
                                    </td>

                                    <td className="px-5 py-4">
                                        {
                                            product.unit
                                        }
                                    </td>

                                    <td className="px-5 py-4">
                                        <div className="flex gap-3">
                                            <Pencil
                                                size={
                                                    16
                                                }
                                            />

                                            <Trash2
                                                size={
                                                    16
                                                }
                                            />
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