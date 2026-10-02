import {
    Pencil,
    Trash2,
} from 'lucide-react';

import type {
    Product,
} from '../../types/databaseCenter';

interface Props {
    data: Product[];
    onEdit: (product: Product) => void;
    onDelete: (productId: number) => void;
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
    onEdit,
    onDelete,
}: Props) {
    return (
        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="w-full min-w-[1000px] text-left text-sm">
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
                                Standard Price
                            </th>

                            <th className="px-5 py-4">
                                Material
                            </th>

                            <th className="px-5 py-4">
                                Grade
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
                                    key={product.id}
                                    className="border-b border-gray-100 last:border-0"
                                >
                                    <td className="px-5 py-4 text-gray-500">
                                        {index + 1}
                                    </td>

                                    <td className="px-5 py-4">
                                        <div className="font-semibold">
                                            {product.name}
                                        </div>
                                    </td>

                                    <td className="px-5 py-4">
                                        <span className="rounded border border-gray-300 bg-gray-50 px-2 py-1 font-mono text-xs">
                                            {product.code}
                                        </span>
                                    </td>

                                    <td className="px-5 py-4 font-semibold">
                                        {rupiah(
                                            Number(
                                                product.std_price
                                            )
                                        )}
                                    </td>

                                    <td className="px-5 py-4">
                                        <div className="flex flex-wrap gap-1.5">
                                            {product.materials?.length ? (
                                                product.materials.map(
                                                    (item) => (
                                                        <span
                                                            key={
                                                                item.id
                                                            }
                                                            className="rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-700"
                                                        >
                                                            {item.material?.name ??
                                                                '-'}
                                                        </span>
                                                    )
                                                )
                                            ) : (
                                                <span className="text-gray-400">
                                                    -
                                                </span>
                                            )}
                                        </div>
                                    </td>

                                    <td className="px-5 py-4">
                                        {product.grade?.name ??
                                            '-'}
                                    </td>

                                    <td className="px-5 py-4">
                                        <div className="flex gap-3">
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    onEdit(product)
                                                }
                                                className="text-gray-500 transition hover:text-emerald-600"
                                            >
                                                <Pencil size={16} />
                                            </button>

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    onDelete(product.id)
                                                }
                                                className="text-gray-500 transition hover:text-red-600"
                                            >
                                                <Trash2 size={16} />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            )
                        )}

                        {data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-5 py-10 text-center text-gray-500"
                                >
                                    Belum ada data produk.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}