import {
    Plus,
    Save,
    Trash2,
    RotateCcw,
} from 'lucide-react';

import {
    useEffect,
    useState,
} from 'react';

import type {
    Inquiry,
    InquiryDetail,
} from '../../types/inquiry';

interface Props {
    inquiry: Inquiry;
}

const inputClass = `
    min-w-[110px]
    w-full
    rounded-md
    border border-transparent
    bg-transparent
    px-2 py-1.5
    text-xs
    outline-none
    transition
    hover:border-gray-300
    focus:border-[#19875f]
    focus:bg-white
`;

const priceInputClass = `
    ${inputClass}
    text-right
`;

const productOptions = [
    {
        name: 'Black Pepper Ground 550GL',
        code: 'SSN-BP-GR01',
        pricelist: 115000,
    },
    {
        name: 'Allspice Ground',
        code: 'SSN-ASP-GR02',
        pricelist: 145000,
    },
    {
        name: 'Allspice Crushed',
        code: 'SSN-ASP-CR01',
        pricelist: 140000,
    },
];

const sourceAPOptions = [
    'Local Supplier',
    'Import',
    'Warehouse Stock',
    'Existing Contract',
    'Spot Market',
];

export default function InquiryDetailTable({
    inquiry,
}: Props) {
    const [rows, setRows] = useState<InquiryDetail[]>(
        inquiry.details
    );

    const [originalRows, setOriginalRows] =
        useState<InquiryDetail[]>(
            inquiry.details
        );

    useEffect(() => {
        setRows(inquiry.details);
        setOriginalRows(inquiry.details);
    }, [inquiry.details]);

    function addRow() {
        const newRow: InquiryDetail = {
            id: `new-${Date.now()}`,

            item: '',
            itemCode: '',
            qty: '',

            sourceAP: '',

            lastOrderDate: null,
            lastOrderPrice: null,

            pricelist: null,
            alternativePrice: null,
            recommendedPrice: null,

            approvedPrice: null,
            approvedDate: null,

            offer1: null,
            offer2: null,
            offer3: null,

            finalPrice: null,

            note: '',

            isNew: true,
        };

        setRows((prev) => [
            ...prev,
            newRow,
        ]);
    }

    function updateRow<K extends keyof InquiryDetail>(
        rowId: InquiryDetail['id'],
        key: K,
        value: InquiryDetail[K],
    ) {
        setRows((prev) =>
            prev.map((row) =>
                row.id === rowId
                    ? {
                        ...row,
                        [key]: value,
                    }
                    : row
            )
        );
    }

    function deleteRow(
        rowId: InquiryDetail['id']
    ) {
        const row = rows.find(
            (item) => item.id === rowId
        );

        if (!row) return;

        if (
            !window.confirm(
                `Hapus item "${row.item || 'baris baru'}"?`
            )
        ) {
            return;
        }

        setRows((prev) =>
            prev.filter(
                (item) =>
                    item.id !== rowId
            )
        );

        // nanti:
        // router.delete(route('inquiry-detail.destroy', rowId))
    }

    function resetRow(
        rowId: InquiryDetail['id']
    ) {
        const original =
            originalRows.find(
                (row) =>
                    row.id === rowId
            );

        if (!original) {
            // row baru → hapus saja
            setRows((prev) =>
                prev.filter(
                    (row) =>
                        row.id !== rowId
                )
            );

            return;
        }

        setRows((prev) =>
            prev.map((row) =>
                row.id === rowId
                    ? {
                        ...original,
                    }
                    : row
            )
        );
    }

    function saveRow(
        row: InquiryDetail
    ) {
        console.log(
            'SAVE DETAIL',
            row
        );

        /*
        nanti ketika backend sudah dibuat:

        if (row.isNew) {
            router.post(
                route('inquiry-detail.store', inquiry.id),
                row,
                {
                    preserveScroll: true,
                }
            );
        } else {
            router.put(
                route('inquiry-detail.update', row.id),
                row,
                {
                    preserveScroll: true,
                }
            );
        }
        */

        const savedRow = {
            ...row,
            isNew: false,
        };

        setRows((prev) =>
            prev.map((item) =>
                item.id === row.id
                    ? savedRow
                    : item
            )
        );

        setOriginalRows((prev) => {
            const exists =
                prev.some(
                    (item) =>
                        item.id === row.id
                );

            if (exists) {
                return prev.map(
                    (item) =>
                        item.id ===
                            row.id
                            ? savedRow
                            : item
                );
            }

            return [
                ...prev,
                savedRow,
            ];
        });
    }

    return (
        <div>
            <div className="mb-4 flex items-center justify-between">
                <div>
                    <h3 className="text-sm font-bold text-gray-900">
                        Detail Pesanan
                    </h3>

                    <p className="mt-1 text-xs text-gray-500">
                        Klik langsung pada cell untuk mengedit data.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={addRow}
                    className="
                        flex items-center gap-2
                        rounded-lg
                        bg-[#19875f]
                        px-3 py-2
                        text-xs font-semibold
                        text-white
                        hover:bg-[#146e4e]
                    "
                >
                    <Plus size={15} />

                    Tambah Baris
                </button>
            </div>

            <div
                className="
                    overflow-x-auto
                    rounded-xl
                    border border-gray-200
                    bg-white
                "
            >
                <table
                    className="
                        w-full
                        min-w-[2500px]
                        border-collapse
                        text-xs
                    "
                >
                    <thead className="sticky top-0 bg-gray-50">
                        <tr>
                            <HeaderCell>
                                No
                            </HeaderCell>

                            <HeaderCell>
                                Item
                            </HeaderCell>

                            <HeaderCell>
                                Item Code
                            </HeaderCell>

                            <HeaderCell>
                                Qty (Kg)
                            </HeaderCell>

                            <HeaderCell>
                                Source AP
                            </HeaderCell>

                            <HeaderCell>
                                Last Order Date
                            </HeaderCell>

                            <HeaderCell>
                                Last Order Price
                            </HeaderCell>

                            <HeaderCell>
                                Pricelist
                            </HeaderCell>

                            <HeaderCell>
                                Alternative Price
                            </HeaderCell>

                            <HeaderCell>
                                Recommended Price
                            </HeaderCell>

                            <HeaderCell>
                                Approved Price
                            </HeaderCell>

                            <HeaderCell>
                                Approved Date
                            </HeaderCell>

                            <HeaderCell>
                                Offer 1
                            </HeaderCell>

                            <HeaderCell>
                                Offer 2
                            </HeaderCell>

                            <HeaderCell>
                                Offer 3
                            </HeaderCell>

                            <HeaderCell>
                                Final Price
                            </HeaderCell>

                            <HeaderCell>
                                Note
                            </HeaderCell>

                            <HeaderCell sticky>
                                Aksi
                            </HeaderCell>
                        </tr>
                    </thead>

                    <tbody>
                        {rows.length ===
                            0 ? (
                            <tr>
                                <td
                                    colSpan={
                                        18
                                    }
                                    className="px-6 py-10 text-center text-sm text-gray-500"
                                >
                                    Belum ada detail pesanan.
                                    Klik{' '}
                                    <strong>
                                        Tambah Baris
                                    </strong>{' '}
                                    untuk mulai input.
                                </td>
                            </tr>
                        ) : (
                            rows.map(
                                (
                                    row,
                                    index
                                ) => (
                                    <tr
                                        key={
                                            row.id
                                        }
                                        className={`
                                            border-t border-gray-100
                                            ${row.isNew
                                                ? 'bg-emerald-50/40'
                                                : 'bg-white'
                                            }
                                        `}
                                    >
                                        <Cell>
                                            {
                                                index +
                                                1
                                            }
                                        </Cell>

                                        <EditableCell>
                                            <select
                                                value={row.item}
                                                onChange={(e) => {
                                                    const selected = productOptions.find(
                                                        (item) => item.name === e.target.value
                                                    );

                                                    updateRow(
                                                        row.id,
                                                        'item',
                                                        e.target.value
                                                    );

                                                    if (selected) {
                                                        updateRow(
                                                            row.id,
                                                            'itemCode',
                                                            selected.code
                                                        );

                                                        updateRow(
                                                            row.id,
                                                            'pricelist',
                                                            selected.pricelist
                                                        );
                                                    }
                                                }}
                                                className={inputClass}
                                            >
                                                <option value="">
                                                    Pilih Item
                                                </option>

                                                {productOptions.map((item) => (
                                                    <option
                                                        key={item.code}
                                                        value={item.name}
                                                    >
                                                        {item.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </EditableCell>

                                        <EditableCell>
                                            <input
                                                value={row.itemCode}
                                                readOnly
                                                className={`${inputClass} bg-gray-50 text-gray-500`}
                                                placeholder="Auto"
                                            />
                                        </EditableCell>

                                        <EditableCell>
                                            <input
                                                type="number"
                                                min="0"
                                                value={
                                                    row.qty
                                                }
                                                onChange={(
                                                    e
                                                ) =>
                                                    updateRow(
                                                        row.id,
                                                        'qty',
                                                        e
                                                            .target
                                                            .value ===
                                                            ''
                                                            ? ''
                                                            : Number(
                                                                e
                                                                    .target
                                                                    .value
                                                            )
                                                    )
                                                }
                                                className={
                                                    priceInputClass
                                                }
                                            />
                                        </EditableCell>

                                        <EditableCell>
                                            <select
                                                value={row.sourceAP}
                                                onChange={(e) =>
                                                    updateRow(
                                                        row.id,
                                                        'sourceAP',
                                                        e.target.value
                                                    )
                                                }
                                                className={inputClass}
                                            >
                                                <option value="">
                                                    Pilih Source AP
                                                </option>

                                                {sourceAPOptions.map((source) => (
                                                    <option
                                                        key={source}
                                                        value={source}
                                                    >
                                                        {source}
                                                    </option>
                                                ))}
                                            </select>
                                        </EditableCell>

                                        <EditableCell>
                                            <input
                                                type="date"
                                                value={
                                                    row.lastOrderDate ??
                                                    ''
                                                }
                                                onChange={(
                                                    e
                                                ) =>
                                                    updateRow(
                                                        row.id,
                                                        'lastOrderDate',
                                                        e
                                                            .target
                                                            .value ||
                                                        null
                                                    )
                                                }
                                                className={
                                                    inputClass
                                                }
                                            />
                                        </EditableCell>

                                        <PriceCell
                                            row={
                                                row
                                            }
                                            field="lastOrderPrice"
                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        <PriceCell
                                            row={
                                                row
                                            }
                                            field="pricelist"
                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        <PriceCell
                                            row={
                                                row
                                            }
                                            field="alternativePrice"
                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        <PriceCell
                                            row={
                                                row
                                            }
                                            field="recommendedPrice"
                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        <PriceCell
                                            row={
                                                row
                                            }
                                            field="approvedPrice"
                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        <EditableCell>
                                            <input
                                                type="date"
                                                value={
                                                    row.approvedDate ??
                                                    ''
                                                }
                                                onChange={(
                                                    e
                                                ) =>
                                                    updateRow(
                                                        row.id,
                                                        'approvedDate',
                                                        e
                                                            .target
                                                            .value ||
                                                        null
                                                    )
                                                }
                                                className={
                                                    inputClass
                                                }
                                            />
                                        </EditableCell>

                                        <PriceCell
                                            row={
                                                row
                                            }
                                            field="offer1"
                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        <PriceCell
                                            row={
                                                row
                                            }
                                            field="offer2"
                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        <PriceCell
                                            row={
                                                row
                                            }
                                            field="offer3"
                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        <PriceCell
                                            row={
                                                row
                                            }
                                            field="finalPrice"
                                            updateRow={
                                                updateRow
                                            }
                                            highlight
                                        />

                                        <EditableCell>
                                            <input
                                                value={
                                                    row.note
                                                }
                                                onChange={(
                                                    e
                                                ) =>
                                                    updateRow(
                                                        row.id,
                                                        'note',
                                                        e
                                                            .target
                                                            .value
                                                    )
                                                }
                                                className={`${inputClass} min-w-[200px]`}
                                                placeholder="Note..."
                                            />
                                        </EditableCell>

                                        <td
                                            className="
                                                sticky right-0
                                                border-l border-gray-100
                                                bg-white
                                                px-3 py-2
                                            "
                                        >
                                            <div className="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        saveRow(
                                                            row
                                                        )
                                                    }
                                                    title="Simpan"
                                                    className="
                                                        rounded-md
                                                        p-1.5
                                                        text-emerald-700
                                                        hover:bg-emerald-50
                                                    "
                                                >
                                                    <Save
                                                        size={
                                                            15
                                                        }
                                                    />
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        resetRow(
                                                            row.id
                                                        )
                                                    }
                                                    title="Batalkan perubahan"
                                                    className="
                                                        rounded-md
                                                        p-1.5
                                                        text-gray-500
                                                        hover:bg-gray-100
                                                    "
                                                >
                                                    <RotateCcw
                                                        size={
                                                            15
                                                        }
                                                    />
                                                </button>

                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        deleteRow(
                                                            row.id
                                                        )
                                                    }
                                                    title="Hapus"
                                                    className="
                                                        rounded-md
                                                        p-1.5
                                                        text-red-500
                                                        hover:bg-red-50
                                                    "
                                                >
                                                    <Trash2
                                                        size={
                                                            15
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

function HeaderCell({
    children,
    sticky = false,
}: {
    children: React.ReactNode;
    sticky?: boolean;
}) {
    return (
        <th
            className={`
                whitespace-nowrap
                border-b border-gray-200
                px-3 py-3
                text-left
                font-semibold
                text-gray-700
                ${sticky
                    ? 'sticky right-0 z-10 border-l bg-gray-50'
                    : ''
                }
            `}
        >
            {children}
        </th>
    );
}

function Cell({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <td className="whitespace-nowrap px-3 py-2 text-gray-600">
            {children}
        </td>
    );
}

function EditableCell({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <td className="border-l border-gray-100 px-1 py-1">
            {children}
        </td>
    );
}

type PriceField =
    | 'lastOrderPrice'
    | 'pricelist'
    | 'alternativePrice'
    | 'recommendedPrice'
    | 'approvedPrice'
    | 'offer1'
    | 'offer2'
    | 'offer3'
    | 'finalPrice';

function PriceCell({
    row,
    field,
    updateRow,
    highlight = false,
}: {
    row: InquiryDetail;
    field: PriceField;
    updateRow: <K extends keyof InquiryDetail>(
        rowId: InquiryDetail['id'],
        key: K,
        value: InquiryDetail[K]
    ) => void;
    highlight?: boolean;
}) {
    return (
        <EditableCell>
            <input
                type="number"
                min="0"
                value={
                    row[field] ??
                    ''
                }
                onChange={(e) =>
                    updateRow(
                        row.id,
                        field,
                        e.target
                            .value === ''
                            ? null
                            : Number(
                                e
                                    .target
                                    .value
                            )
                    )
                }
                className={`
                    ${priceInputClass}
                    ${highlight
                        ? 'font-semibold text-emerald-700'
                        : ''
                    }
                `}
            />
        </EditableCell>
    );
}