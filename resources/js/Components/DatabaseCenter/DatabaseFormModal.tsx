import { useEffect, useState } from 'react';
import { X } from 'lucide-react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';

import type {
    Customer,
    DatabaseTab,
    Division,
    RiskLevel,
} from '../../types/databaseCenter';

interface Props {
    open: boolean;
    activeTab: DatabaseTab;
    onClose: () => void;

    editingCustomer?: Customer | null;

    segmentations: SegmentationOption[];
}

interface SegmentationOption {
    id: number;
    name: string;
}

export default function DatabaseFormModal({
    open,
    activeTab,
    onClose,
    editingCustomer = null,
    segmentations = [],
}: Props) {
    const [customerForm, setCustomerForm] = useState({
        company: '',
        address: '',
        segmentationId: '',
        level: 'Low' as RiskLevel,
        division: 'Industri' as Division,
        pic: '',
        phone: '',
    });

    const [productForm, setProductForm] = useState({
        name: '',
        description: '',
        itemCode: '',
        category: '',
        sterilization: 'S (Steam)',
        price: '',
        unit: 'KG',
    });

    const [competitorForm, setCompetitorForm] = useState({
        competitor: '',
        product: '',
        qty: '',
        price: '',
        division: 'Industri' as Division,
        notes: '',
        recordedAt: new Date().toISOString().slice(0, 10),
    });

    // Close modal with Escape
    useEffect(() => {
        if (!open) return;

        const handleEscape = (
            event: KeyboardEvent
        ) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener(
            'keydown',
            handleEscape
        );

        return () => {
            window.removeEventListener(
                'keydown',
                handleEscape
            );
        };
    }, [open, onClose]);


    // Fill form when editing customer
    useEffect(() => {
        if (!open) return;

        if (
            activeTab === 'customer' &&
            editingCustomer
        ) {
            setCustomerForm({
                company:
                    editingCustomer.company ?? '',

                address:
                    editingCustomer.address ?? '',

                segmentationId:
                    editingCustomer.segmentationId
                        ?.toString() ?? '',

                level:
                    editingCustomer.level,

                division:
                    editingCustomer.division,

                pic:
                    editingCustomer.pic ?? '',

                phone:
                    editingCustomer.phone ?? '',
            });

            return;
        }

        if (activeTab === 'customer') {
            setCustomerForm({
                company: '',
                address: '',
                segmentationId: '',
                level: 'Low',
                division: 'Industri',
                pic: '',
                phone: '',
            });
        }
    }, [
        open,
        activeTab,
        editingCustomer,
    ]);

    if (!open) {
        return null;
    }

    function submitForm(event: React.FormEvent) {
        event.preventDefault();

        if (activeTab === 'customer') {
            const payload = {
                name: customerForm.company,
                address: customerForm.address,
                segmentation_id: customerForm.segmentationId
                    ? Number(
                        customerForm.segmentationId
                    )
                    : null,
                level: customerForm.level,
                divisi: customerForm.division,
                pic: customerForm.pic,
                phone: customerForm.phone,
            };

            if (editingCustomer) {
                router.put(
                    route(
                        'customers.update',
                        editingCustomer.id
                    ),
                    payload,
                    {
                        preserveScroll: true,
                        onSuccess: () => onClose(),
                    }
                );

                return;
            }

            router.post(
                route('customers.store'),
                payload,
                {
                    preserveScroll: true,
                    onSuccess: () => onClose(),
                }
            );

            return;
        }

        if (activeTab === 'product') {
            console.log('Product:', productForm);
        }

        if (activeTab === 'competitor') {
            console.log('Competitor:', competitorForm);
        }

        onClose();
    }

    const title =
        activeTab === 'customer'
            ? editingCustomer
                ? 'Edit Customer'
                : 'Tambah Customer'
            : activeTab === 'product'
                ? 'Tambah Produk'
                : 'Tambah Data Kompetitor';

    return (
        <div
            className="
                fixed inset-0 z-50
                flex items-center justify-center
                bg-black/40
                p-4
            "
            onMouseDown={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div
                className="
                    max-h-[90vh]
                    w-full max-w-3xl
                    overflow-y-auto
                    rounded-2xl
                    bg-white
                    shadow-2xl
                "
            >
                {/* Header */}
                <div
                    className="
                        sticky top-0 z-10
                        flex items-center justify-between
                        border-b border-gray-200
                        bg-white
                        px-6 py-5
                    "
                >
                    <div>
                        <h2 className="text-xl font-bold text-gray-900">
                            {title}
                        </h2>

                        <p className="mt-1 text-sm text-gray-500">
                            Lengkapi data berikut lalu simpan.
                        </p>
                    </div>

                    <button
                        type="button"
                        onClick={onClose}
                        className="
                            rounded-lg p-2
                            text-gray-500
                            transition
                            hover:bg-gray-100
                            hover:text-gray-900
                        "
                    >
                        <X size={20} />
                    </button>
                </div>

                <form onSubmit={submitForm}>
                    <div className="space-y-5 p-6">
                        {activeTab === 'customer' && (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Nama Perusahaan">
                                        <input
                                            required
                                            value={customerForm.company}
                                            onChange={(e) =>
                                                setCustomerForm({
                                                    ...customerForm,
                                                    company: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="Contoh: PT Maju Jaya"
                                        />
                                    </Field>

                                    <Field label="Segmentasi">
                                        <select
                                            required
                                            value={
                                                customerForm.segmentationId
                                            }
                                            onChange={(e) =>
                                                setCustomerForm({
                                                    ...customerForm,
                                                    segmentationId:
                                                        e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                        >
                                            <option value="">
                                                Pilih Segmentasi
                                            </option>

                                            {segmentations.map(
                                                (segmentation) => (
                                                    <option
                                                        key={
                                                            segmentation.id
                                                        }
                                                        value={
                                                            segmentation.id
                                                        }
                                                    >
                                                        {
                                                            segmentation.name
                                                        }
                                                    </option>
                                                )
                                            )}
                                        </select>
                                    </Field>
                                </div>

                                <Field label="Alamat">
                                    <textarea
                                        required
                                        rows={3}
                                        value={customerForm.address}
                                        onChange={(e) =>
                                            setCustomerForm({
                                                ...customerForm,
                                                address: e.target.value,
                                            })
                                        }
                                        className={inputClass}
                                        placeholder="Alamat pabrik / kantor"
                                    />
                                </Field>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Level Risiko">
                                        <select
                                            value={customerForm.level}
                                            onChange={(e) =>
                                                setCustomerForm({
                                                    ...customerForm,
                                                    level: e.target
                                                        .value as RiskLevel,
                                                })
                                            }
                                            className={inputClass}
                                        >
                                            <option value="Low">
                                                Low
                                            </option>
                                            <option value="Medium">
                                                Medium
                                            </option>
                                            <option value="High">
                                                High
                                            </option>
                                        </select>
                                    </Field>

                                    <Field label="Divisi">
                                        <select
                                            value={customerForm.division}
                                            onChange={(e) =>
                                                setCustomerForm({
                                                    ...customerForm,
                                                    division: e.target
                                                        .value as Division,
                                                })
                                            }
                                            className={inputClass}
                                        >
                                            <option value="Industri">
                                                Industri
                                            </option>
                                            <option value="SME">SME</option>
                                            <option value="Low Cost">
                                                Low Cost
                                            </option>
                                            <option value="All">
                                                All
                                            </option>
                                        </select>
                                    </Field>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Nama Narahubung">
                                        <input
                                            required
                                            value={customerForm.pic}
                                            onChange={(e) =>
                                                setCustomerForm({
                                                    ...customerForm,
                                                    pic: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="Nama Narahubung"
                                        />
                                    </Field>

                                    <Field label="Nomor Telepon">
                                        <input
                                            value={customerForm.phone}
                                            onChange={(e) =>
                                                setCustomerForm({
                                                    ...customerForm,
                                                    phone: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="08xx..."
                                        />
                                    </Field>
                                </div>
                            </>
                        )}

                        {activeTab === 'product' && (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Nama Produk">
                                        <input
                                            required
                                            value={productForm.name}
                                            onChange={(e) =>
                                                setProductForm({
                                                    ...productForm,
                                                    name: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="Contoh: Black Pepper Ground"
                                        />
                                    </Field>

                                    <Field label="Item Code">
                                        <input
                                            required
                                            value={productForm.itemCode}
                                            onChange={(e) =>
                                                setProductForm({
                                                    ...productForm,
                                                    itemCode: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="SSN-BP-GR01"
                                        />
                                    </Field>
                                </div>

                                <Field label="Deskripsi">
                                    <textarea
                                        rows={3}
                                        value={productForm.description}
                                        onChange={(e) =>
                                            setProductForm({
                                                ...productForm,
                                                description: e.target.value,
                                            })
                                        }
                                        className={inputClass}
                                        placeholder="Deskripsi produk..."
                                    />
                                </Field>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Kategori">
                                        <input
                                            required
                                            value={productForm.category}
                                            onChange={(e) =>
                                                setProductForm({
                                                    ...productForm,
                                                    category:
                                                        e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="Spices & Herbs"
                                        />
                                    </Field>

                                    <Field label="Sterilisasi">
                                        <select
                                            value={
                                                productForm.sterilization
                                            }
                                            onChange={(e) =>
                                                setProductForm({
                                                    ...productForm,
                                                    sterilization:
                                                        e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                        >
                                            <option value="S (Steam)">
                                                S (Steam)
                                            </option>
                                            <option value="NS">
                                                NS (Non-Sterilized)
                                            </option>
                                            <option value="ETO">ETO</option>
                                        </select>
                                    </Field>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Standard Pricelist">
                                        <input
                                            required
                                            type="number"
                                            min="0"
                                            value={productForm.price}
                                            onChange={(e) =>
                                                setProductForm({
                                                    ...productForm,
                                                    price: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="140000"
                                        />
                                    </Field>

                                    <Field label="Satuan">
                                        <select
                                            value={productForm.unit}
                                            onChange={(e) =>
                                                setProductForm({
                                                    ...productForm,
                                                    unit: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                        >
                                            <option value="KG">KG</option>
                                            <option value="PCS">PCS</option>
                                            <option value="BOX">BOX</option>
                                            <option value="BAG">BAG</option>
                                        </select>
                                    </Field>
                                </div>
                            </>
                        )}

                        {activeTab === 'competitor' && (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Nama Kompetitor">
                                        <input
                                            required
                                            value={
                                                competitorForm.competitor
                                            }
                                            onChange={(e) =>
                                                setCompetitorForm({
                                                    ...competitorForm,
                                                    competitor:
                                                        e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="Contoh: Cahaya Pelita"
                                        />
                                    </Field>

                                    <Field label="Produk">
                                        <input
                                            required
                                            value={competitorForm.product}
                                            onChange={(e) =>
                                                setCompetitorForm({
                                                    ...competitorForm,
                                                    product: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="Pilih produk"
                                        />
                                    </Field>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Qty (KG)">
                                        <input
                                            required
                                            type="number"
                                            min="0"
                                            value={competitorForm.qty}
                                            onChange={(e) =>
                                                setCompetitorForm({
                                                    ...competitorForm,
                                                    qty: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="100"
                                        />
                                    </Field>

                                    <Field label="Harga Kompetitor">
                                        <input
                                            required
                                            type="number"
                                            min="0"
                                            value={competitorForm.price}
                                            onChange={(e) =>
                                                setCompetitorForm({
                                                    ...competitorForm,
                                                    price: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                            placeholder="125000"
                                        />
                                    </Field>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Divisi">
                                        <select
                                            value={
                                                competitorForm.division
                                            }
                                            onChange={(e) =>
                                                setCompetitorForm({
                                                    ...competitorForm,
                                                    division: e.target
                                                        .value as Division,
                                                })
                                            }
                                            className={inputClass}
                                        >
                                            <option value="Industri">
                                                Industri
                                            </option>
                                            <option value="SME">SME</option>
                                            <option value="Low Cost">
                                                Low Cost
                                            </option>
                                            <option value="All">
                                                All
                                            </option>
                                        </select>
                                    </Field>

                                    <Field label="Tanggal Pencatatan">
                                        <input
                                            required
                                            type="date"
                                            value={
                                                competitorForm.recordedAt
                                            }
                                            onChange={(e) =>
                                                setCompetitorForm({
                                                    ...competitorForm,
                                                    recordedAt:
                                                        e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                        />
                                    </Field>
                                </div>

                                <Field label="Keterangan">
                                    <textarea
                                        rows={3}
                                        value={competitorForm.notes}
                                        onChange={(e) =>
                                            setCompetitorForm({
                                                ...competitorForm,
                                                notes: e.target.value,
                                            })
                                        }
                                        className={inputClass}
                                        placeholder="Keterangan harga / kondisi pasar..."
                                    />
                                </Field>
                            </>
                        )}
                    </div>

                    {/* Footer */}
                    <div
                        className="
                            sticky bottom-0
                            flex justify-end gap-3
                            border-t border-gray-200
                            bg-white
                            px-6 py-4
                        "
                    >
                        <button
                            type="button"
                            onClick={onClose}
                            className="
                                rounded-lg
                                border border-gray-300
                                bg-white
                                px-5 py-2.5
                                text-sm font-semibold
                                text-gray-700
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
                                px-5 py-2.5
                                text-sm font-semibold
                                text-white
                                transition
                                hover:bg-[#146e4e]
                            "
                        >
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

const inputClass = `
    w-full rounded-lg
    border border-gray-300
    bg-white
    px-3 py-2.5
    text-sm text-gray-900
    outline-none
    transition
    placeholder:text-gray-400
    focus:border-[#19875f]
    focus:ring-2
    focus:ring-[#19875f]/10
`;

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