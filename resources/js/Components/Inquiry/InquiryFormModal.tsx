import axios from 'axios';
import { ChevronDown, Search, X } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useEffect, useId, useState } from 'react';
import { reserveInquiryCode } from '../../Services/InquiryPrintApi';
import type { Inquiry } from '../../types/inquiry';

interface CustomerOption {
    id: number;
    name: string;
    code?: string | null;
    division?: string | null;
    riskLevel?: string | null;
}

interface PicOption {
    id: number;
    name: string;
}

interface SegmentationOption {
    id: number;
    name: string;
}

interface Props {
    open: boolean;

    inquiry?: Inquiry | null;

    customers: CustomerOption[];

    pics: PicOption[];

    segmentations: SegmentationOption[];

    defaultPicId?: number;

    onClose: () => void;
}

interface InquiryFormState {
    code: string;

    date: string;

    etd: string;

    pic: number | string;

    customerId: string;

    segmentationId: string;

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
    segmentationId: '',
    shippingRate: '',
    note: '',
};

export default function InquiryFormModal({
    open,
    inquiry = null,
    customers,
    pics,
    segmentations,
    defaultPicId,
    onClose,
}: Props) {
    const [
        form,
        setForm,
    ] = useState<InquiryFormState>(
        initialForm
    );
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [codeError, setCodeError] = useState('');
    const [isReservingCode, setIsReservingCode] = useState(false);
    const [reservationVersion, setReservationVersion] = useState(0);

    useEffect(() => {
        if (!open) {
            return;
        }

        setErrors({});
        setCodeError('');
        setForm(inquiry
            ? {
                code: inquiry.code,
                date: inquiry.date.slice(0, 10),
                etd: inquiry.etd?.slice(0, 10) ?? '',
                pic: inquiry.pic ?? '',
                customerId: inquiry.customerId?.toString() ?? '',
                segmentationId: inquiry.segmentationId?.toString() ?? '',
                shippingRate: inquiry.shippingRate?.toString() ?? '',
                note: inquiry.note ?? '',
            }
            : {
                ...initialForm,
                date: getLocalDate(),
                pic: defaultPicId !== undefined
                    && pics.some((pic) => Number(pic.id) === defaultPicId)
                    ? defaultPicId
                    : '',
            });
    }, [open, inquiry, defaultPicId, pics]);

    useEffect(() => {
        if (!open || inquiry) {
            setIsReservingCode(false);

            return;
        }

        if (!form.date || !form.pic) {
            setForm((currentForm) => ({ ...currentForm, code: '' }));
            setIsReservingCode(false);

            return;
        }

        let isCurrentRequest = true;
        setIsReservingCode(true);
        setCodeError('');

        reserveInquiryCode(form.date, Number(form.pic))
            .then((code) => {
                if (isCurrentRequest) {
                    setForm((currentForm) => ({ ...currentForm, code }));
                    setErrors((currentErrors) => {
                        const nextErrors = { ...currentErrors };
                        delete nextErrors.code;

                        return nextErrors;
                    });
                }
            })
            .catch((error: unknown) => {
                if (isCurrentRequest) {
                    setForm((currentForm) => ({ ...currentForm, code: '' }));
                    setCodeError(getReservationErrorMessage(error));
                }
            })
            .finally(() => {
                if (isCurrentRequest) {
                    setIsReservingCode(false);
                }
            });

        return () => {
            isCurrentRequest = false;
        };
    }, [open, inquiry, form.date, form.pic, reservationVersion]);

    if (!open) {
        return null;
    }

    function resetForm() {
        setForm(initialForm);
        setErrors({});
        setCodeError('');
        setIsReservingCode(false);
    }

    function handleClose() {
        resetForm();

        onClose();
    }

    function submit(
        event: React.FormEvent
    ) {
        event.preventDefault();

        const payload = {
            code: form.code,
            date: form.date,
            etd: form.etd || null,
            pic: form.pic ? Number(form.pic) : null,
            customer_id: form.customerId ? Number(form.customerId) : null,
            segmentation_id: form.segmentationId ? Number(form.segmentationId) : null,
            shipping_rate: form.shippingRate ? Number(form.shippingRate) : null,
            note: form.note || null,
        };

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                resetForm();
                onClose();
            },
            onError: (validationErrors: Record<string, string>) => {
                setErrors(validationErrors);

                if (!inquiry && validationErrors.code) {
                    setForm((currentForm) => ({ ...currentForm, code: '' }));
                    setReservationVersion((version) => version + 1);
                }
            },
        };

        if (inquiry) {
            router.put(`/inquiry/${inquiry.id}`, payload, options);
        } else {
            router.post('/inquiry', payload, options);
        }
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
                            {inquiry ? 'Edit Inquiry' : 'Tambah Inquiry'}
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
                    {Object.keys(errors).length > 0 && (
                        <div role="alert" className="mx-6 mt-5 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
                            {Object.values(errors).join(' ')}
                        </div>
                    )}
                    <div
                        className="
                            grid
                            gap-4
                            p-6
                            md:grid-cols-2
                        "
                    >
                        {/* CODE */}
                        <Field label={inquiry ? 'Inquiry Code' : 'Inquiry Code (Otomatis)'}>
                            <input
                                required
                                readOnly={!inquiry}

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

                                placeholder={inquiry ? 'Kode Inquiry' : isReservingCode ? 'Memesan kode...' : 'Kode akan ditampilkan otomatis'}
                            />
                            {codeError && (
                                <p role="alert" className="mt-1 text-xs text-red-600">
                                    {codeError}
                                </p>
                            )}
                            {!inquiry && (
                                <p className="mt-1 text-xs text-gray-500">
                                    {isReservingCode
                                        ? 'Sedang menyiapkan kode Inquiry.'
                                        : 'Kode mengikuti tanggal, Kode Sales PIC, dan nomor urut harian.'}
                                </p>
                            )}
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
                                ) => {
                                    setForm({
                                        ...form,
                                        date: e.target.value,
                                        code: '',
                                    });
                                    setCodeError('');
                                }}

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
                                    e) => {
                                    setForm({
                                        ...form,
                                        pic: e.target.value,
                                        code: '',
                                    });
                                    setCodeError('');
                                }}

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
                            <CustomerSearchSelect
                                customers={customers}
                                value={form.customerId}
                                onChange={(customerId) =>
                                    setForm({
                                        ...form,
                                        customerId,
                                    })
                                }
                            />
                        </Field>

                        {/* SEGMENTATION */}
                        <Field label="Segmentasi">
                            <select
                                value={form.segmentationId}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        segmentationId: e.target.value,
                                    })
                                }
                                className={inputClass}
                            >
                                <option value="">Pilih Segmentasi</option>
                                {segmentations.map((segmentation) => (
                                    <option key={segmentation.id} value={segmentation.id}>
                                        {segmentation.name}
                                    </option>
                                ))}
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
                            disabled={!inquiry && (!form.code || isReservingCode)}

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
                            {inquiry ? 'Simpan Perubahan' : 'Simpan Inquiry'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function CustomerSearchSelect({
    customers,
    value,
    onChange,
}: {
    customers: CustomerOption[];
    value: string;
    onChange: (value: string) => void;
}) {
    const listId = useId();
    const [isOpen, setIsOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [activeIndex, setActiveIndex] = useState(0);
    const selectedCustomer = customers.find(
        (customer) => String(customer.id) === value
    );
    const selectedLabel = selectedCustomer
        ? formatCustomerLabel(selectedCustomer)
        : '';
    const filteredCustomers = customers.filter((customer) => {
        const searchText = `${customer.code ?? ''} ${customer.name}`.toLocaleLowerCase();

        return searchText.includes(searchTerm.trim().toLocaleLowerCase());
    });

    function selectCustomer(customer: CustomerOption | null) {
        onChange(customer ? String(customer.id) : '');
        setSearchTerm('');
        setIsOpen(false);
    }

    return (
        <div
            className="relative"
            onBlur={(event) => {
                if (
                    !(event.relatedTarget instanceof Node)
                    || !event.currentTarget.contains(event.relatedTarget)
                ) {
                    setIsOpen(false);
                    setSearchTerm('');
                }
            }}
        >
            <div className="relative">
                <Search
                    size={16}
                    aria-hidden="true"
                    className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                />
                <input
                    type="text"
                    role="combobox"
                    aria-label="Cari customer berdasarkan kode atau nama company"
                    aria-autocomplete="list"
                    aria-expanded={isOpen}
                    aria-controls={listId}
                    autoComplete="off"
                    value={isOpen ? searchTerm : selectedLabel}
                    onFocus={() => {
                        setSearchTerm('');
                        setActiveIndex(0);
                        setIsOpen(true);
                    }}
                    onChange={(event) => {
                        setSearchTerm(event.target.value);
                        setActiveIndex(0);
                        setIsOpen(true);
                    }}
                    onKeyDown={(event) => {
                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            setActiveIndex((index) =>
                                Math.min(index + 1, filteredCustomers.length)
                            );
                        } else if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            setActiveIndex((index) => Math.max(index - 1, 0));
                        } else if (event.key === 'Enter' && isOpen) {
                            event.preventDefault();
                            selectCustomer(
                                activeIndex === 0
                                    ? null
                                    : filteredCustomers[activeIndex - 1] ?? null
                            );
                        } else if (event.key === 'Escape') {
                            setIsOpen(false);
                            setSearchTerm('');
                        }
                    }}
                    placeholder="Cari kode atau nama company..."
                    className={`${inputClass} pl-9 pr-9`}
                />
                <ChevronDown
                    size={16}
                    aria-hidden="true"
                    className={`pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition ${isOpen ? 'rotate-180' : ''}`}
                />
            </div>

            {isOpen && (
                <div
                    id={listId}
                    role="listbox"
                    aria-label="Daftar customer"
                    className="absolute top-full z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                >
                    <button
                        type="button"
                        role="option"
                        aria-selected={!value}
                        onClick={() => selectCustomer(null)}
                        className={`w-full px-3 py-2 text-left text-sm text-gray-500 hover:bg-gray-50 ${activeIndex === 0 ? 'bg-gray-50' : ''}`}
                    >
                        Pilih Customer
                    </button>
                    {filteredCustomers.map((customer, index) => (
                        <button
                            key={customer.id}
                            type="button"
                            role="option"
                            aria-selected={String(customer.id) === value}
                            onClick={() => selectCustomer(customer)}
                            className={`w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 ${activeIndex === index + 1 ? 'bg-gray-50' : ''}`}
                        >
                            {formatCustomerLabel(customer)}
                        </button>
                    ))}
                    {filteredCustomers.length === 0 && (
                        <p className="px-3 py-2 text-sm text-gray-500">
                            Customer tidak ditemukan.
                        </p>
                    )}
                </div>
            )}
        </div>
    );
}

function formatCustomerLabel(customer: CustomerOption): string {
    return customer.code
        ? `${customer.code} — ${customer.name}`
        : customer.name;
}

function getLocalDate(): string {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function getReservationErrorMessage(error: unknown): string {
    if (axios.isAxiosError<{ errors?: Record<string, string[]>; message?: string }>(error)) {
        const errors = error.response?.data?.errors;
        const message = errors?.pic?.[0] ?? errors?.date?.[0] ?? error.response?.data?.message;

        return message ?? 'Kode Inquiry gagal disiapkan. Silakan coba lagi.';
    }

    return 'Kode Inquiry gagal disiapkan. Silakan coba lagi.';
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