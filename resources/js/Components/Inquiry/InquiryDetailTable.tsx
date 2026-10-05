import {
    Plus,
    RotateCcw,
    Save,
    Trash2,
} from 'lucide-react';

import {
    router,
} from '@inertiajs/react';

import {
    useEffect,
    useState,
} from 'react';

import type {
    Inquiry,
    InquiryDetail,
} from '../../types/inquiry';

/*
|--------------------------------------------------------------------------
| Props Options
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

interface CompetitorPrice {
    id: number;
    competitorId: number;
    competitorName: string;
    productId: number;
    price: number | null;
    date: string | null;
    note?: string | null;
}

interface Props {
    inquiry: Inquiry;

    products: ProductOption[];

    grades: GradeOption[];

    competitorPrices: CompetitorPrice[];
}

/*
|--------------------------------------------------------------------------
| Styling
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Component
|--------------------------------------------------------------------------
*/

export default function InquiryDetailTable({
    inquiry,

    products,

    grades,

    competitorPrices,
}: Props) {
    const [
        rows,
        setRows,
    ] = useState<InquiryDetail[]>(
        inquiry.details
    );

    const [
        originalRows,
        setOriginalRows,
    ] = useState<InquiryDetail[]>(
        inquiry.details
    );

    /*
    |--------------------------------------------------------------------------
    | Sync Backend Data
    |--------------------------------------------------------------------------
    */

    useEffect(() => {
        setRows(
            inquiry.details
        );

        setOriginalRows(
            inquiry.details
        );
    }, [
        inquiry.details,
    ]);

    /*
    |--------------------------------------------------------------------------
    | Find Competitor Price
    |--------------------------------------------------------------------------
    |
    | competitorPrices dikirim Controller dengan urutan date DESC.
    | Jadi .find() akan mengambil harga terbaru.
    |
    */

    function findCompetitorPrice(
        competitorId: number,
        productId: number
    ): number | null {
        const result =
            competitorPrices.find(
                (price) =>
                    price.competitorId ===
                    competitorId &&
                    price.productId ===
                    productId
            );

        return result?.price ??
            null;
    }

    /*
    |--------------------------------------------------------------------------
    | Find Competitor Price Date
    |--------------------------------------------------------------------------
    */

    function findCompetitorPriceDate(
        competitorId: number,
        productId: number
    ): string | null {
        const result =
            competitorPrices.find(
                (price) =>
                    price.competitorId ===
                    competitorId &&
                    price.productId ===
                    productId
            );

        return result?.date ??
            null;
    }

    function getSourceAPOptions(
        productId: number | null
    ) {
        if (!productId) {
            return [];
        }

        const filtered =
            competitorPrices.filter(
                (item) =>
                    item.productId ===
                    productId
            );

        return Array.from(
            new Map(
                filtered.map(
                    (item) => [
                        item.competitorId,
                        {
                            id:
                                item.competitorId,

                            name:
                                item.competitorName,

                            price:
                                item.price,

                            date:
                                item.date,
                        },
                    ]
                )
            ).values()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Add Row
    |--------------------------------------------------------------------------
    */

    function addRow() {
        const newRow: InquiryDetail = {
            id: `new-${Date.now()}`,

            productId: null,

            gradeId: null,

            qty: '',

            productStdPrice:
                null,

            sourceAP:
                null,

            /*
            | sekarang setelah
            | Source AP secara UI
            */
            alternativePrice:
                null,

            dateAP:
                null,

            referencePrice:
                null,

            lastOrderDate:
                null,

            lastOrderPrice:
                null,

            lastQuotationDate:
                null,

            lastQuotationPrice:
                null,

            recommendedPrice:
                null,

            approvedPrice:
                null,

            approvedDate:
                null,

            offer1Price:
                null,

            offer2Price:
                null,

            offer3Price:
                null,

            finalPrice:
                null,

            note: '',

            product:
                null,

            grade:
                null,

            competitor:
                null,

            isNew: true,
        };

        setRows((prev) => [
            ...prev,
            newRow,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update Row
    |--------------------------------------------------------------------------
    */

    function updateRow<
        K extends keyof InquiryDetail
    >(
        rowId: InquiryDetail['id'],
        key: K,
        value: InquiryDetail[K]
    ) {
        setRows((prev) =>
            prev.map((row) =>
                row.id === rowId
                    ? {
                        ...row,
                        [key]:
                            value,
                    }
                    : row
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Select Product
    |--------------------------------------------------------------------------
    */

    function handleProductChange(
        row: InquiryDetail,
        value: string
    ) {
        const productId =
            value
                ? Number(value)
                : null;

        const product =
            products.find(
                (item) =>
                    item.id ===
                    productId
            );

        /*
        | Update Product
        */

        updateRow(
            row.id,
            'productId',
            productId
        );


        updateRow(
            row.id,
            'product',
            product
                ? {
                    id:
                        product.id,

                    name:
                        product.name,

                    code:
                        product.code,
                }
                : null
        );

        /*
        | Product Std Price
        */

        updateRow(
            row.id,
            'productStdPrice',
            product?.stdPrice ??
            null
        );

        if (
            productId &&
            row.sourceAP
        ) {
            updateRow(
                row.id,
                'alternativePrice',
                findCompetitorPrice(
                    row.sourceAP,
                    productId
                )
            );

            updateRow(
                row.id,
                'dateAP',
                findCompetitorPriceDate(
                    row.sourceAP,
                    productId
                )
            );
        } else {
            updateRow(
                row.id,
                'alternativePrice',
                null
            );

            updateRow(
                row.id,
                'dateAP',
                null
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Select Source AP / Competitor
    |--------------------------------------------------------------------------
    */

    function handleSourceAPChange(
        row: InquiryDetail,
        value: string
    ) {
        const competitorId =
            value
                ? Number(value)
                : null;

        updateRow(
            row.id,
            'sourceAP',
            competitorId
        );

        if (
            competitorId &&
            row.productId
        ) {
            const competitorPrice =
                competitorPrices.find(
                    (item) =>
                        item.competitorId ===
                        competitorId &&
                        item.productId ===
                        row.productId
                );

            updateRow(
                row.id,
                'alternativePrice',
                competitorPrice?.price ??
                null
            );

            updateRow(
                row.id,
                'dateAP',
                competitorPrice?.date ??
                null
            );
        } else {
            updateRow(
                row.id,
                'alternativePrice',
                null
            );

            updateRow(
                row.id,
                'dateAP',
                null
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    function deleteRow(
        rowId: InquiryDetail['id']
    ) {
        const row =
            rows.find(
                (item) =>
                    item.id ===
                    rowId
            );

        if (!row) {
            return;
        }

        if (
            !window.confirm(
                'Hapus detail inquiry ini?'
            )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Belum tersimpan DB
        */

        if (row.isNew) {
            setRows((prev) =>
                prev.filter(
                    (item) =>
                        item.id !==
                        rowId
                )
            );

            return;
        }


        router.delete(
            `/inquiry/detail/${rowId}`,
            {
                preserveScroll: true,
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reset
    |--------------------------------------------------------------------------
    */

    function resetRow(
        rowId: InquiryDetail['id']
    ) {
        const original =
            originalRows.find(
                (row) =>
                    row.id ===
                    rowId
            );

        /*
        | row baru
        */

        if (!original) {
            setRows((prev) =>
                prev.filter(
                    (row) =>
                        row.id !==
                        rowId
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

    /*
    |--------------------------------------------------------------------------
    | Save
    |--------------------------------------------------------------------------
    */

    function saveRow(
        row: InquiryDetail
    ) {
        if (!row.productId) {
            window.alert(
                'Product wajib dipilih.'
            );

            return;
        }

        const payload = {
            product_id:
                row.productId,

            grade_id:
                row.gradeId,

            qty:
                row.qty === ''
                    ? null
                    : row.qty,

            source_ap:
                row.sourceAP,

            date_ap:
                row.dateAP,

            reference_price:
                row.referencePrice,

            last_order_date:
                row.lastOrderDate,

            last_order_price:
                row.lastOrderPrice,

            last_quotation_date:
                row.lastQuotationDate,

            last_quotation_price:
                row.lastQuotationPrice,

            recommended_price:
                row.recommendedPrice,

            approved_price:
                row.approvedPrice,

            approved_date:
                row.approvedDate,

            offer_1_price:
                row.offer1Price,

            offer_2_price:
                row.offer2Price,

            offer_3_price:
                row.offer3Price,

            final_price:
                row.finalPrice,

            note:
                row.note,
        };

        /*
        |--------------------------------------------------------------------------
        | CREATE
        |--------------------------------------------------------------------------
        */

        if (row.isNew) {
            router.post(
                `/inquiry/${inquiry.id}/detail`,
                payload,
                {
                    preserveScroll: true,
                }
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        router.put(
            `/inquiry/detail/${row.id}`,
            payload,
            {
                preserveScroll: true,
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    return (
        <div>

            {/* TITLE */}
            <div className="mb-4 flex items-center gap-4 justify-left">
                <div>
                    <h3 className="text-sm font-bold text-gray-900">
                        Detail Pesanan
                    </h3>

                    <p className="mt-1 text-xs text-gray-500">
                        Klik langsung pada
                        cell untuk mengedit
                        data.
                    </p>
                </div>

                <button
                    type="button"

                    onClick={
                        addRow
                    }

                    className="
                        flex
                        items-center
                        gap-2
                        rounded-lg
                        bg-[#19875f]
                        px-3
                        py-2
                        text-xs
                        font-semibold
                        text-white
                        transition
                        hover:bg-[#146e4e]
                    "
                >
                    <Plus
                        size={15}
                    />

                    Tambah Baris
                </button>
            </div>

            {/* TABLE */}
            <div
                className="
                    overflow-x-auto
                    rounded-xl
                    border
                    border-gray-200
                    bg-white
                "
            >
                <table
                    className="
                        w-full
                        min-w-[3100px]
                        border-collapse
                        text-xs
                    "
                >
                    <thead className="sticky top-0 z-10 bg-gray-50">
                        <tr>
                            <HeaderCell>
                                No
                            </HeaderCell>

                            <HeaderCell>
                                Product / Item
                            </HeaderCell>

                            <HeaderCell>
                                Item Code
                            </HeaderCell> 

                            <HeaderCell>
                                Grade
                            </HeaderCell>

                            <HeaderCell>
                                Qty (Kg)
                            </HeaderCell>

                            <HeaderCell>
                                Product Std Price
                            </HeaderCell>

                            {/* PINDAH KE SINI */}
                            <HeaderCell>
                                Source AP
                            </HeaderCell>

                            {/* LANGSUNG SEBELAH SOURCE AP */}
                            <HeaderCell>
                                Alternative Price
                            </HeaderCell>

                            <HeaderCell>
                                Date AP
                            </HeaderCell>

                            <HeaderCell>
                                Reference Price
                            </HeaderCell>

                            <HeaderCell>
                                Last Order Date
                            </HeaderCell>

                            <HeaderCell>
                                Last Order Price
                            </HeaderCell>

                            <HeaderCell>
                                Last Quotation Date
                            </HeaderCell>

                            <HeaderCell>
                                Last Quotation Price
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

                            <HeaderCell
                                sticky
                            >
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
                                        23
                                    }

                                    className="px-6 py-3 text-center text-sm text-gray-500"
                                >
                                    Belum ada detail pesanan.

                                    {' '}

                                    <strong>
                                        Tambah Baris
                                    </strong>

                                    {' '}

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
                                            border-t
                                            border-gray-100

                                            ${row.isNew
                                                ? 'bg-emerald-50/40'
                                                : 'bg-white'
                                            }
                                        `}
                                    >
                                        {/* NO */}
                                        <Cell>
                                            {
                                                index +
                                                1
                                            }

                                            {/* SAVE */}
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
                                        </Cell>

                                        {/* PRODUCT */}
                                        <EditableCell>
                                            <select
                                                value={
                                                    row.productId ??
                                                    ''
                                                }

                                                onChange={(
                                                    e
                                                ) =>
                                                    handleProductChange(
                                                        row,
                                                        e
                                                            .target
                                                            .value
                                                    )
                                                }

                                                className={
                                                    inputClass
                                                }
                                            >
                                                <option value="">
                                                    Pilih
                                                    Product
                                                </option>

                                                {products.map(
                                                    (
                                                        product
                                                    ) => (
                                                        <option
                                                            key={
                                                                product.id
                                                            }

                                                            value={
                                                                product.id
                                                            }
                                                        >
                                                            {
                                                                product.name
                                                            }
                                                        </option>
                                                    )
                                                )}
                                            </select>
                                        </EditableCell>

                                        {/* ITEM CODE */}
                                        <EditableCell>
                                            <input
                                                value={
                                                    row
                                                        .product
                                                        ?.code ??
                                                    ''
                                                }

                                                readOnly

                                                placeholder="Auto"

                                                className={`
                                                    ${inputClass}
                                                    cursor-not-allowed
                                                    bg-gray-50
                                                    text-gray-500
                                                `}
                                            />
                                        </EditableCell>

                                        {/* GRADE */}
                                        <EditableCell>
                                            <select
                                                value={
                                                    row.gradeId ??
                                                    ''
                                                }

                                                onChange={(
                                                    e
                                                ) =>
                                                    updateRow(
                                                        row.id,
                                                        'gradeId',
                                                        e
                                                            .target
                                                            .value
                                                            ? Number(
                                                                e
                                                                    .target
                                                                    .value
                                                            )
                                                            : null
                                                    )
                                                }

                                                className={
                                                    inputClass
                                                }
                                            >
                                                <option value="">
                                                    Pilih
                                                    Grade
                                                </option>

                                                {grades.map(
                                                    (
                                                        grade
                                                    ) => (
                                                        <option
                                                            key={
                                                                grade.id
                                                            }

                                                            value={
                                                                grade.id
                                                            }
                                                        >
                                                            {
                                                                grade.name
                                                            }
                                                        </option>
                                                    )
                                                )}
                                            </select>
                                        </EditableCell>

                                        {/* QTY */}
                                        <EditableCell>
                                            <input
                                                type="number"

                                                min="0"

                                                step="0.001"

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

                                        {/* PRODUCT STD PRICE */}
                                        <ReadOnlyPriceCell
                                            value={
                                                row.productStdPrice
                                            }
                                        />

                                        {/* SOURCE AP */}
                                        <EditableCell>
                                            <select
                                                value={
                                                    row.sourceAP ??
                                                    ''
                                                }

                                                disabled={
                                                    !row.productId
                                                }

                                                onChange={(e) =>
                                                    handleSourceAPChange(
                                                        row,
                                                        e.target.value
                                                    )
                                                }

                                                className={`${inputClass}${!row.productId
                                                    ? 'cursor-not-allowed bg-gray-50 text-gray-400'
                                                    : ''
                                                    }
        `}
                                            >
                                                <option value="">
                                                    {row.productId
                                                        ? 'Pilih Source AP'
                                                        : 'Pilih Product dahulu'}
                                                </option>

                                                {getSourceAPOptions(
                                                    row.productId
                                                ).map(
                                                    (source) => (
                                                        <option
                                                            key={source.id}
                                                            value={source.id}
                                                        >
                                                            {source.name}
                                                        </option>
                                                    )
                                                )}
                                            </select>
                                        </EditableCell>

                                        {/* ALTERNATIVE PRICE */}
                                        {/* SEKARANG LANGSUNG SETELAH SOURCE AP */}
                                        <ReadOnlyPriceCell
                                            value={
                                                row.alternativePrice
                                            }

                                            highlight
                                        />

                                        {/* DATE AP */}
                                        <EditableCell>
                                            <input
                                                type="date"

                                                value={
                                                    row.dateAP ??
                                                    ''
                                                }

                                                readOnly

                                                className={`
                                                    ${inputClass}
                                                    cursor-not-allowed
                                                    bg-gray-50
                                                    text-gray-500
                                                `}
                                            />
                                        </EditableCell>

                                        {/* REFERENCE PRICE */}
                                        <PriceCell
                                            row={
                                                row
                                            }

                                            field="referencePrice"

                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        {/* LAST ORDER DATE */}
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

                                        {/* LAST ORDER PRICE */}
                                        <PriceCell
                                            row={
                                                row
                                            }

                                            field="lastOrderPrice"

                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        {/* LAST QUOTATION DATE */}
                                        <EditableCell>
                                            <input
                                                type="date"

                                                value={
                                                    row.lastQuotationDate ??
                                                    ''
                                                }

                                                onChange={(
                                                    e
                                                ) =>
                                                    updateRow(
                                                        row.id,
                                                        'lastQuotationDate',
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

                                        {/* LAST QUOTATION PRICE */}
                                        <PriceCell
                                            row={
                                                row
                                            }

                                            field="lastQuotationPrice"

                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        {/* RECOMMENDED */}
                                        <PriceCell
                                            row={
                                                row
                                            }

                                            field="recommendedPrice"

                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        {/* APPROVED PRICE */}
                                        <PriceCell
                                            row={
                                                row
                                            }

                                            field="approvedPrice"

                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        {/* APPROVED DATE */}
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

                                        {/* OFFER 1 */}
                                        <PriceCell
                                            row={
                                                row
                                            }

                                            field="offer1Price"

                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        {/* OFFER 2 */}
                                        <PriceCell
                                            row={
                                                row
                                            }

                                            field="offer2Price"

                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        {/* OFFER 3 */}
                                        <PriceCell
                                            row={
                                                row
                                            }

                                            field="offer3Price"

                                            updateRow={
                                                updateRow
                                            }
                                        />

                                        {/* FINAL PRICE */}
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

                                        {/* NOTE */}
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

                                                className={`
                                                    ${inputClass}
                                                    min-w-[200px]
                                                `}

                                                placeholder="Note..."
                                            />
                                        </EditableCell>

                                        {/* ACTION */}
                                        <td
                                            className="
                                                sticky
                                                right-0
                                                border-l
                                                border-gray-100
                                                bg-white
                                                px-3
                                                py-2
                                            "
                                        >
                                            <div className="flex items-center gap-2">

                                                {/* SAVE */}
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

                                                {/* RESET */}
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

                                                {/* DELETE */}
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

/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

function HeaderCell({
    children,
    sticky = false,
}: {
    children:
    React.ReactNode;

    sticky?: boolean;
}) {
    return (
        <th
            className={`
                whitespace-nowrap
                border-b
                border-gray-200
                px-3
                py-3
                text-left
                font-semibold
                text-gray-700

                ${sticky
                    ? 'sticky right-0 z-20 border-l bg-gray-50'
                    : ''
                }
            `}
        >
            {children}
        </th>
    );
}

/*
|--------------------------------------------------------------------------
| Normal Cell
|--------------------------------------------------------------------------
*/

function Cell({
    children,
}: {
    children:
    React.ReactNode;
}) {
    return (
        <td className="whitespace-nowrap px-3 py-2 text-gray-600">
            {children}
        </td>
    );
}

/*
|--------------------------------------------------------------------------
| Editable Cell
|--------------------------------------------------------------------------
*/

function EditableCell({
    children,
}: {
    children:
    React.ReactNode;
}) {
    return (
        <td className="border-l border-gray-100 px-1 py-1">
            {children}
        </td>
    );
}

/*
|--------------------------------------------------------------------------
| Editable Price Fields
|--------------------------------------------------------------------------
*/

type PriceField =
    | 'referencePrice'
    | 'lastOrderPrice'
    | 'lastQuotationPrice'
    | 'recommendedPrice'
    | 'approvedPrice'
    | 'offer1Price'
    | 'offer2Price'
    | 'offer3Price'
    | 'finalPrice';

/*
|--------------------------------------------------------------------------
| Price Cell
|--------------------------------------------------------------------------
*/

function PriceCell({
    row,
    field,
    updateRow,
    highlight = false,
}: {
    row:
    InquiryDetail;

    field:
    PriceField;

    updateRow: <
        K extends keyof InquiryDetail
    >(
        rowId:
            InquiryDetail['id'],

        key: K,

        value:
            InquiryDetail[K]
    ) => void;

    highlight?: boolean;
}) {
    return (
        <EditableCell>
            <input
                type="number"

                min="0"

                step="0.01"

                value={
                    row[field] ??
                    ''
                }

                onChange={(
                    e
                ) =>
                    updateRow(
                        row.id,
                        field,

                        e
                            .target
                            .value ===
                            ''
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

/*
|--------------------------------------------------------------------------
| Read Only Price Cell
|--------------------------------------------------------------------------
*/

function ReadOnlyPriceCell({
    value,
    highlight = false,
}: {
    value:
    number | null;

    highlight?: boolean;
}) {
    return (
        <EditableCell>
            <input
                type="number"

                value={
                    value ??
                    ''
                }

                readOnly

                placeholder="Auto"

                className={`
                    ${priceInputClass}

                    cursor-not-allowed
                    bg-gray-50

                    ${highlight
                        ? 'font-semibold text-blue-700'
                        : 'text-gray-600'
                    }
                `}
            />
        </EditableCell>
    );
}