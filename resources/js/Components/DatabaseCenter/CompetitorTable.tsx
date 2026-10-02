import {
    Pencil,
    Trash2,
} from 'lucide-react';

import type {
    Competitor,
} from '../../types/databaseCenter';

interface Props {
    data: Competitor[];

    onEdit: (
        competitor: Competitor
    ) => void;

    onDelete: (
        competitorProductId: number
    ) => void;
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

export default function CompetitorTable({
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
                                Competitor
                            </th>

                            <th className="px-5 py-4">
                                Product
                            </th>

                            <th className="px-5 py-4">
                                Price Competitor
                            </th>

                            <th className="px-5 py-4">
                                Divisi
                            </th>

                            <th className="px-5 py-4">
                                Keterangan
                            </th>

                            <th className="px-5 py-4">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        {data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-5 py-10 text-center text-gray-400"
                                >
                                    Belum ada data competitor.
                                </td>
                            </tr>
                        ) : (
                            data.map(
                                (
                                    item,
                                    index
                                ) => (
                                    <tr
                                        key={
                                            item.id
                                        }
                                        className="border-b border-gray-100 last:border-0"
                                    >
                                        <td className="px-5 py-4 text-gray-500">
                                            {index +
                                                1}
                                        </td>

                                        <td className="px-5 py-4 font-semibold">
                                            {
                                                item.competitor
                                            }
                                        </td>

                                        <td className="px-5 py-4 text-emerald-800">
                                            {
                                                item.product
                                            }
                                        </td>

                                        <td className="px-5 py-4 font-semibold">
                                            {rupiah(
                                                Number(
                                                    item.price
                                                )
                                            )}
                                        </td>

                                        <td className="px-5 py-4">
                                            {
                                                item.division
                                            }
                                        </td>

                                        <td className="px-5 py-4">
                                            <div>
                                                {item.notes || '-'}
                                            </div>

                                            <div className="mt-1 text-xs text-gray-400">
                                                Recorded: {item.date}
                                            </div>
                                        </td>

                                        <td className="px-5 py-4">
                                            <div className="flex gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        onEdit(
                                                            item
                                                        )
                                                    }
                                                    className="rounded-lg p-2 text-gray-600 transition hover:bg-gray-100 hover:text-emerald-700"
                                                    title="Edit competitor"
                                                >
                                                    <Pencil
                                                        size={
                                                            16
                                                        }
                                                    />
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        onDelete?.(
                                                            item.id
                                                        )
                                                    }
                                                    className="rounded-lg p-2 text-gray-600 transition hover:bg-red-50 hover:text-red-600"
                                                    title="Hapus competitor"
                                                >
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
                            )
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}