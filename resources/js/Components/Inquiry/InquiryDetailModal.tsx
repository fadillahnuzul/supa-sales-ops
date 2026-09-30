import { X } from 'lucide-react';
import { useState } from 'react';

interface Props {
    open: boolean;
    inquiryId: number | null;
    onClose: () => void;
}

const inputClass = `
    w-full rounded-lg
    border border-gray-300
    px-3 py-2.5
    text-sm
    outline-none
    focus:border-[#19875f]
    focus:ring-2
    focus:ring-[#19875f]/10
`;

export default function InquiryDetailModal({
    open,
    inquiryId,
    onClose,
}: Props) {
    const [form, setForm] = useState({
        item: '',
        itemCode: '',
        qty: '',
        sourceAP: '',
        lastOrderDate: '',
        lastOrderPrice: '',
        pricelist: '',
        alternativePrice: '',
        recommendedPrice: '',
        approvedPrice: '',
        approvedDate: '',
        offer1: '',
        offer2: '',
        offer3: '',
        finalPrice: '',
        note: '',
    });

    if (!open) return null;

    function submit(
        event: React.FormEvent
    ) {
        event.preventDefault();

        console.log(
            'Inquiry:',
            inquiryId,
            form
        );

        onClose();
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="max-h-[92vh] w-full max-w-6xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div className="sticky top-0 z-10 flex items-center justify-between border-b border-gray-200 bg-white px-6 py-5">
                    <div>
                        <h2 className="text-xl font-bold">
                            Tambah Detail Pesanan
                        </h2>

                        <p className="mt-1 text-sm text-gray-500">
                            Lengkapi data item dan pricing.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                    >
                        <X size={20} />
                    </button>
                </div>

                <form onSubmit={submit}>
                    <div className="grid gap-4 p-6 md:grid-cols-2 lg:grid-cols-3">
                        <Field label="Item">
                            <input
                                value={form.item}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        item: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Item Code">
                            <input
                                value={form.itemCode}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        itemCode: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Qty (Kg)">
                            <input
                                type="number"
                                value={form.qty}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        qty: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Source AP">
                            <input
                                value={form.sourceAP}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        sourceAP: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Last Order Date">
                            <input
                                type="date"
                                value={form.lastOrderDate}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        lastOrderDate: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <PriceField
                            label="Last Order Price"
                            value={form.lastOrderPrice}
                            onChange={(value) =>
                                setForm({
                                    ...form,
                                    lastOrderPrice: value,
                                })
                            }
                        />

                        <PriceField
                            label="Pricelist"
                            value={form.pricelist}
                            onChange={(value) =>
                                setForm({
                                    ...form,
                                    pricelist: value,
                                })
                            }
                        />

                        <PriceField
                            label="Alternative Price"
                            value={form.alternativePrice}
                            onChange={(value) =>
                                setForm({
                                    ...form,
                                    alternativePrice: value,
                                })
                            }
                        />

                        <PriceField
                            label="Recommended Price"
                            value={form.recommendedPrice}
                            onChange={(value) =>
                                setForm({
                                    ...form,
                                    recommendedPrice: value,
                                })
                            }
                        />

                        <PriceField
                            label="Approved Price"
                            value={form.approvedPrice}
                            onChange={(value) =>
                                setForm({
                                    ...form,
                                    approvedPrice: value,
                                })
                            }
                        />

                        <Field label="Approved Date">
                            <input
                                type="date"
                                value={form.approvedDate}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        approvedDate: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <PriceField
                            label="Offer 1"
                            value={form.offer1}
                            onChange={(value) =>
                                setForm({
                                    ...form,
                                    offer1: value,
                                })
                            }
                        />

                        <PriceField
                            label="Offer 2"
                            value={form.offer2}
                            onChange={(value) =>
                                setForm({
                                    ...form,
                                    offer2: value,
                                })
                            }
                        />

                        <PriceField
                            label="Offer 3"
                            value={form.offer3}
                            onChange={(value) =>
                                setForm({
                                    ...form,
                                    offer3: value,
                                })
                            }
                        />

                        <PriceField
                            label="Final Price"
                            value={form.finalPrice}
                            onChange={(value) =>
                                setForm({
                                    ...form,
                                    finalPrice: value,
                                })
                            }
                        />

                        <div className="md:col-span-2 lg:col-span-3">
                            <Field label="Note">
                                <textarea
                                    rows={3}
                                    value={form.note}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            note: e.target.value,
                                        })
                                    }
                                    className={inputClass}
                                />
                            </Field>
                        </div>
                    </div>

                    <div className="sticky bottom-0 flex justify-end gap-3 border-t border-gray-200 bg-white px-6 py-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            className="rounded-lg bg-[#19875f] px-5 py-2.5 text-sm font-semibold text-white"
                        >
                            Simpan Detail
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
    children: React.ReactNode;
}) {
    return (
        <label>
            <span className="mb-1.5 block text-sm font-semibold text-gray-700">
                {label}
            </span>

            {children}
        </label>
    );
}

function PriceField({
    label,
    value,
    onChange,
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
}) {
    return (
        <Field label={label}>
            <div className="relative">
                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
                    Rp
                </span>

                <input
                    type="number"
                    min="0"
                    value={value}
                    onChange={(e) =>
                        onChange(e.target.value)
                    }
                    className={`${inputClass} pl-9`}
                />
            </div>
        </Field>
    );
}