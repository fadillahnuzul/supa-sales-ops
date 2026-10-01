import { X } from 'lucide-react';
import { useState } from 'react';

interface Props {
    open: boolean;
    onClose: () => void;
}

const inputClass = `
    w-full rounded-lg
    border border-gray-300
    bg-white
    px-3 py-2.5
    text-sm
    outline-none
    focus:border-[#19875f]
    focus:ring-2
    focus:ring-[#19875f]/10
`;

export default function InquiryFormModal({
    open,
    onClose,
}: Props) {
    const [form, setForm] = useState({
        inquiryCode: '',
        inquiryDate: '',
        etd: '',
        picSales: '',
        customer: '',
        segmentation: '',
        level: 'Low',
        sterilization: 'Steam',
    });

    if (!open) return null;

    function submit(
        event: React.FormEvent
    ) {
        event.preventDefault();

        console.log(form);

        onClose();
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div className="w-full max-w-3xl rounded-2xl bg-white shadow-2xl">
                <div className="flex items-center justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <h2 className="text-xl font-bold">
                            Tambah Inquiry
                        </h2>

                        <p className="mt-1 text-sm text-gray-500">
                            Buat inquiry induk terlebih dahulu.
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
                    <div className="grid gap-4 p-6 md:grid-cols-2">
                        <Field label="Inquiry Code">
                            <input
                                required
                                value={form.inquiryCode}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        inquiryCode: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Inquiry Date">
                            <input
                                required
                                type="date"
                                value={form.inquiryDate}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        inquiryDate: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Estimation Time Delivered (ETD)">
                            <input
                                required
                                type="date"
                                value={form.etd}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        etd: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="PIC Sales">
                            <input
                                required
                                value={form.picSales}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        picSales: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Customer">
                            <input
                                required
                                value={form.customer}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        customer: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Segmentation">
                            <input
                                required
                                value={form.segmentation}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        segmentation: e.target.value,
                                    })
                                }
                                className={inputClass}
                            />
                        </Field>

                        <Field label="Level">
                            <select
                                value={form.level}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        level: e.target.value,
                                    })
                                }
                                className={inputClass}
                            >
                                <option>Low</option>
                                <option>Medium</option>
                                <option>High</option>
                            </select>
                        </Field>

                        <Field label="Sterilization">
                            <select
                                value={form.sterilization}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        sterilization: e.target.value,
                                    })
                                }
                                className={inputClass}
                            >
                                <option>Steam</option>
                                <option>Non-Sterilized</option>
                                <option>ETO</option>
                            </select>
                        </Field>
                    </div>

                    <div className="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
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
    children: React.ReactNode;
}) {
    return (
        <label className="block">
            <span className="mb-1.5 block text-sm font-semibold text-gray-700">
                {label}
            </span>

            {children}
        </label>
    );
}