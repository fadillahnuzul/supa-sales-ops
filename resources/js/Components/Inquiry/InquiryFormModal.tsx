import { X } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface CustomerOption {
    id: number;
    name: string;
    division?: string | null;
    riskLevel?: string | null;
}

interface PicOption {
    id: number;
    name: string;
}

interface Props {
    open: boolean;

    customers: CustomerOption[];

    pics: PicOption[];

    onClose: () => void;
}

interface InquiryFormState {
    code: string;

    date: string;

    etd: string;

    pic: number | string;

    customerId: string;

    shippingRate: string;

    note: string;
}

const inputClass = `
    w-full
    rounded-lg
    border
    border-gray-300
    bg-white
    px-3
    py-2.5
    text-sm
    outline-none
    transition

    focus:border-[#19875f]
    focus:ring-2
    focus:ring-[#19875f]/10
`;

const initialForm: InquiryFormState = {
    code: '',

    date: '',

    etd: '',

    pic: '',

    customerId: '',

    shippingRate: '',

    note: '',
};

export default function InquiryFormModal({
    open,

    customers,

    pics,

    onClose,
}: Props) {
    const [
        form,
        setForm,
    ] = useState<InquiryFormState>(
        initialForm
    );

    if (!open) {
        return null;
    }

    function resetForm() {
        setForm(
            initialForm
        );
    }

    function handleClose() {
        resetForm();

        onClose();
    }

    function submit(
        event: React.FormEvent
    ) {
        event.preventDefault();

        router.post(
            '/inquiry',

            {
                code:
                    form.code,

                date:
                    form.date,

                etd:
                    form.etd ||
                    null,

                pic:
                    form.pic
                        ? Number(
                            form.pic
                        )
                        : null,

                customer_id:
                    form.customerId
                        ? Number(
                            form.customerId
                        )
                        : null,

                shipping_rate:
                    form.shippingRate
                        ? Number(
                            form.shippingRate
                        )
                        : null,

                note:
                    form.note ||
                    null,
            },

            {
                preserveScroll:
                    true,

                onSuccess: () => {
                    resetForm();

                    onClose();
                },
            }
        );
    }

    return (
        <div
            className="
                fixed
                inset-0
                z-50
                flex
                items-center
                justify-center
                bg-black/40
                p-4
            "
        >
            <div
                className="
                    w-full
                    max-w-3xl
                    rounded-2xl
                    bg-white
                    shadow-2xl
                "
            >
                {/* HEADER */}
                <div
                    className="
                        flex
                        items-center
                        justify-between
                        border-b
                        border-gray-200
                        px-6
                        py-5
                    "
                >
                    <div>
                        <h2 className="text-xl font-bold">
                            Tambah Inquiry
                        </h2>

                        <p className="mt-1 text-sm text-gray-500">
                            Buat inquiry
                            induk terlebih
                            dahulu.
                        </p>
                    </div>

                    <button
                        type="button"

                        onClick={
                            handleClose
                        }

                        className="
                            rounded-lg
                            p-2
                            text-gray-500
                            transition
                            hover:bg-gray-100
                            hover:text-gray-700
                        "
                    >
                        <X
                            size={
                                20
                            }
                        />
                    </button>
                </div>

                {/* FORM */}
                <form
                    onSubmit={
                        submit
                    }
                >
                    <div
                        className="
                            grid
                            gap-4
                            p-6
                            md:grid-cols-2
                        "
                    >
                        {/* CODE */}
                        <Field label="Inquiry Code">
                            <input
                                required

                                value={
                                    form.code
                                }

                                onChange={(
                                    e
                                ) =>
                                    setForm({
                                        ...form,

                                        code:
                                            e.target.value,
                                    })
                                } className={inputClass}

                                placeholder="Contoh: INQ-2026-001"
                            />
                        </Field>

                        {/* DATE */}
                        <Field label="Inquiry Date">
                            <input
                                required

                                type="date"

                                value={
                                    form.date
                                }

                                onChange={(
                                    e
                                ) =>
                                    setForm({
                                        ...form,

                                        date:
                                            e.target.value,
                                    })
                                }

                                className={
                                    inputClass
                                }
                            />
                        </Field>

                        {/* ETD */}
                        <Field label="Estimation Time Delivered (ETD)">
                            <input
                                type="date"

                                value={
                                    form.etd
                                }

                                onChange={(
                                    e
                                ) =>
                                    setForm({
                                        ...form,

                                        etd:
                                            e.target.value,
                                    })
                                }

                                className={
                                    inputClass
                                }
                            />
                        </Field>

                        {/* PIC SALES */}
                        <Field label="PIC Sales">
                            <select
                                value={
                                    form.pic
                                }
                                onChange={(
                                    e) =>
                                    setForm({
                                        ...form,
                                        pic:
                                            e.target.value,
                                    })
                                }

                                className={
                                    inputClass
                                }
                            >
                                <option value="">
                                    Pilih PIC Sales
                                </option>

                                {pics.map(
                                    (pic) => (
                                        <option key={pic.id} value={pic.id}>{pic.name}</option>
                                    )
                                )}
                            </select>
                        </Field>

                        {/* CUSTOMER */}
                        <Field label="Customer">
                            <select
                                value={
                                    form.customerId
                                }

                                onChange={(
                                    e
                                ) =>
                                    setForm({
                                        ...form,

                                        customerId:
                                            e.target.value,
                                    })
                                }

                                className={
                                    inputClass
                                }
                            >
                                <option value="">
                                    Pilih Customer
                                </option>

                                {customers.map(
                                    (
                                        customer
                                    ) => (
                                        <option
                                            key={customer.id}
                                            value={customer.id}
                                        >
                                            {customer.name}
                                        </option>
                                    )
                                )}
                            </select>
                        </Field>

                        {/* SHIPPING RATE */}
                        <Field label="Shipping Rate">
                            <div className="relative">
                                <span
                                    className="
                                        absolute
                                        left-3
                                        top-1/2
                                        -translate-y-1/2
                                        text-sm
                                        text-gray-500
                                    "
                                >
                                    Rp
                                </span>

                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value={
                                        form.shippingRate
                                    }

                                    onChange={(
                                        e
                                    ) =>
                                        setForm({
                                            ...form,

                                            shippingRate:
                                                e
                                                    .target
                                                    .value,
                                        })
                                    }

                                    className={`
                                        ${inputClass}
                                        pl-9
                                    `}

                                    placeholder="0"
                                />
                            </div>
                        </Field>

                        {/* NOTE */}
                        <div className="md:col-span-2">
                            <Field label="Note">
                                <textarea
                                    rows={
                                        4
                                    }

                                    value={
                                        form.note
                                    }

                                    onChange={(
                                        e
                                    ) =>
                                        setForm({
                                            ...form,

                                            note:
                                                e
                                                    .target
                                                    .value,
                                        })
                                    }

                                    className={
                                        inputClass
                                    }

                                    placeholder="Catatan inquiry..."
                                />
                            </Field>
                        </div>
                    </div>

                    {/* FOOTER */}
                    <div
                        className="
                            flex
                            justify-end
                            gap-3
                            border-t
                            border-gray-200
                            px-6
                            py-4
                        "
                    >
                        <button
                            type="button"

                            onClick={
                                handleClose
                            }

                            className="
                                rounded-lg
                                border
                                border-gray-300
                                px-5
                                py-2.5
                                text-sm
                                font-semibold
                                text-gray-700
                                transition
                                hover:bg-gray-50
                            "
                        >
                            Batal
                        </button>

                        <button
                            type="submit"

                            className="
                                rounded-lg
                                bg-[#19875f]
                                px-5
                                py-2.5
                                text-sm
                                font-semibold
                                text-white
                                transition
                                hover:bg-[#146e4e]
                            "
                        >
                            Simpan Inquiry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function Field({
    label,

    children,
}: {
    label: string;

    children:
        React.ReactNode;
}) {
    return (
        <label className="block">
            <span
                className="
                    mb-1.5
                    block
                    text-sm
                    font-semibold
                    text-gray-700
                "
            >
                {label}
            </span>

            {children}
        </label>
    );
}