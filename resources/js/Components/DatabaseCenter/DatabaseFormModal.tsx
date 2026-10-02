import { useEffect, useState } from 'react';
import { X } from 'lucide-react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import {
    Plus,
    Trash2,
} from 'lucide-react';

import type {
    Customer,
    DatabaseTab,
    Division,
    RiskLevel,
    Sterilization,
    Product,
    Competitor
} from '../../types/databaseCenter';

interface Props {
    open: boolean;
    activeTab: DatabaseTab;
    onClose: () => void;

    editingCustomer?: Customer | null;
    editingProduct?: Product | null;
    editingCompetitor?: Competitor | null;

    segmentations?: SegmentationOption[];

    materials?: MaterialOption[];
    products?: Product[];
    grades?: GradeOption[];
}

interface SegmentationOption {
    id: number;
    name: string;
}

interface MaterialOption {
    id: number;
    name: string;
    code?: string | null;

    source_type: 'material' | 'product';
    source_key: string;
}

interface GradeOption {
    id: number;
    name: string;
}

export default function DatabaseFormModal({
    open,
    activeTab,
    onClose,

    editingCustomer = null,
    editingProduct = null,
    editingCompetitor = null,

    segmentations = [],
    materials = [],
    products = [],
    grades = [],
}: Props) {
    const [customerForm, setCustomerForm] = useState({
        company: '',
        address: '',
        segmentationId: '',
        sterilization: 'S' as Sterilization,
        level: 'Low' as RiskLevel,
        division: 'Industri' as Division,
        pic: '',
        phone: '',
    });

    const [productForm, setProductForm] = useState({
        name: '',
        code: '',
        stdPrice: '',
        materials: [] as string[],
        gradeId: '',
    });

    const [
        competitorForm,
        setCompetitorForm,
    ] = useState({
        competitor: '',
        productId: '',
        price: '',
        date: new Date()
            .toISOString()
            .slice(0, 10),
        division:
            'Industri' as Division,
        competitorNote: '',
        productNote: '',
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

                sterilization:
                    editingCustomer.sterilization ?? 'S',

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
                sterilization: 'S',
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

    //Edit product
    useEffect(() => {
        if (
            !open ||
            activeTab !== 'product'
        ) {
            return;
        }

        if (editingProduct) {
            setProductForm({
                name:
                    editingProduct.name ??
                    '',

                code:
                    editingProduct.code ??
                    '',

                stdPrice:
                    editingProduct.std_price
                        ?.toString() ??
                    '',

                materials:
                    editingProduct.materials
                        ?.map(
                            (item) =>
                                `${item.material_type}:${item.material_id}`
                        ) ??
                    [],

                gradeId:
                    editingProduct.grade_id
                        ?.toString() ??
                    '',
            });

            return;
        }

        setProductForm({
            name: '',
            code: '',
            stdPrice: '',
            materials: [],
            gradeId: '',
        });
    }, [
        open,
        activeTab,
        editingProduct,
    ]);

    //Edit competitor
    useEffect(() => {
        if (
            !open ||
            activeTab !== 'competitor'
        ) {
            return;
        }

        if (editingCompetitor) {
            setCompetitorForm({
                competitor:
                    editingCompetitor.competitor ??
                    '',

                productId:
                    String(
                        editingCompetitor.product_id
                    ),

                price:
                    String(
                        editingCompetitor.price ??
                        ''
                    ),

                division:
                    editingCompetitor.division,

                competitorNote:
                    editingCompetitor.competitor_note ??
                    '',

                productNote:
                    editingCompetitor.notes ??
                    '',
                date:
                    editingCompetitor.date ??
                    new Date()
                        .toISOString()
                        .slice(0, 10),
            });

            return;
        }

        setCompetitorForm({
            competitor: '',
            productId: '',
            price: '',
            division: 'Industri',
            competitorNote: '',
            productNote: '',
            date: new Date()
                .toISOString()
                .slice(0, 10),
        });
    }, [
        open,
        activeTab,
        editingCompetitor,
    ]);

    if (!open) {
        return null;
    }

    function addProductMaterial() {
        setProductForm(
            (current) => ({
                ...current,

                materials: [
                    ...current.materials,
                    '',
                ],
            })
        );
    }

    function removeProductMaterial(
        index: number
    ) {
        setProductForm(
            (current) => ({
                ...current,

                materials:
                    current.materials.filter(
                        (_, itemIndex) =>
                            itemIndex !==
                            index
                    ),
            })
        );
    }

    function updateProductMaterial(
        index: number,
        value: string
    ) {
        setProductForm(
            (current) => ({
                ...current,

                materials:
                    current.materials.map(
                        (
                            material,
                            itemIndex
                        ) =>
                            itemIndex ===
                                index
                                ? value
                                : material
                    ),
            })
        );
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
                sterilization: customerForm.sterilization,
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
            const payload = {
                name:
                    productForm.name,

                code:
                    productForm.code,

                std_price:
                    Number(
                        productForm.stdPrice
                    ),

                materials:
                    productForm.materials.filter(
                        Boolean
                    ),

                grade_id:
                    productForm.gradeId
                        ? Number(
                            productForm.gradeId
                        )
                        : null,
            };

            if (editingProduct) {
                router.put(
                    route(
                        'products.update',
                        editingProduct.id
                    ),
                    payload,
                    {
                        preserveScroll: true,

                        onSuccess: () => {
                            onClose();
                        },
                    }
                );

                return;
            }

            router.post(
                route(
                    'products.store'
                ),
                payload,
                {
                    preserveScroll: true,

                    onSuccess: () => {
                        onClose();
                    },
                }
            );

            return;
        }

        if (activeTab === 'competitor') {
            const payload = {
                name: competitorForm.competitor,

                divisi: competitorForm.division,

                note:
                    competitorForm.competitorNote ||
                    null,

                product_id: Number(
                    competitorForm.productId
                ),

                price: Number(
                    competitorForm.price
                ),

                date: competitorForm.date,

                product_note:
                    competitorForm.productNote ||
                    null,
            };

            /*
             * ============================
             * UPDATE COMPETITOR
             * ============================
             */
            if (editingCompetitor) {
                router.put(
                    route(
                        'competitors.update',
                        editingCompetitor.id
                    ),

                    payload,

                    {
                        preserveScroll: true,

                        onSuccess: () => {
                            onClose();
                        },

                        onError: (errors) => {
                            const message =
                                errors.product_id ??
                                errors.name ??
                                Object.values(errors)[0];

                            if (message) {
                                window.alert(String(message));
                            }
                        },
                    }
                );

                return;
            }

            router.post(
                route(
                    'competitors.store'
                ),
                payload,
                {
                    preserveScroll: true,

                    onSuccess: () => {
                        onClose();
                    },

                    onError: (errors) => {
                        console.error(
                            'Create competitor error:',
                            errors
                        );
                    },
                }
            );

            return;
        }

        onClose();
    }

    const title =
        activeTab === 'customer'
            ? editingCustomer
                ? 'Edit Customer'
                : 'Tambah Customer'
            : activeTab === 'product'
                ? editingProduct
                    ? 'Edit Produk'
                    : 'Tambah Produk'
                : editingCompetitor
                    ? 'Edit Kompetitor'
                    : 'Tambah Kompetitor';

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
                                            value={customerForm.segmentationId}
                                            onChange={(e) =>
                                                setCustomerForm({
                                                    ...customerForm,
                                                    segmentationId: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                        >
                                            <option value="" disabled>
                                                Pilih segmentasi
                                            </option>
                                            {segmentations.map(
                                                (segmentation) => (
                                                    <option
                                                        key={
                                                            segmentation.id
                                                        }
                                                        value={String(segmentation.id)}
                                                    >
                                                        {segmentation.name}
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

                                <div className="grid gap-4 md:grid-cols-3">
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

                                    <Field label="Sterilisasi">
                                        <select
                                            required
                                            value={customerForm.sterilization}
                                            onChange={(e) =>
                                                setCustomerForm({
                                                    ...customerForm,
                                                    sterilization: e.target.value as Sterilization,
                                                })
                                            }
                                            className={inputClass}
                                        >
                                            <option value="S">
                                                S (Steril)
                                            </option>
                                            <option value="NS">
                                                NS (Non-Steril)
                                            </option>
                                            <option value="SS">
                                                SS (Super Steril)
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
                                            value={
                                                productForm.name
                                            }
                                            onChange={(e) =>
                                                setProductForm({
                                                    ...productForm,

                                                    name:
                                                        e.target.value,
                                                })
                                            }
                                            className={
                                                inputClass
                                            }
                                            placeholder="Black Pepper Ground"
                                        />
                                    </Field>

                                    <Field label="Item Code">
                                        <input
                                            required
                                            value={
                                                productForm.code
                                            }
                                            onChange={(e) =>
                                                setProductForm({
                                                    ...productForm,

                                                    code:
                                                        e.target.value,
                                                })
                                            }
                                            className={
                                                inputClass
                                            }
                                            placeholder="SSN-BP-GN01"
                                        />
                                    </Field>
                                </div>


                                <div className="grid gap-4 md:grid-cols-2">
                                    <Field label="Standard Price">
                                        <input
                                            required
                                            type="number"
                                            min="0"
                                            value={
                                                productForm.stdPrice
                                            }
                                            onChange={(e) =>
                                                setProductForm({
                                                    ...productForm,

                                                    stdPrice:
                                                        e.target.value,
                                                })
                                            }
                                            className={
                                                inputClass
                                            }
                                            placeholder="140000"
                                        />
                                    </Field>


                                    <Field label="Grade">
                                        <select
                                            value={
                                                productForm.gradeId
                                            }
                                            onChange={(e) =>
                                                setProductForm({
                                                    ...productForm,

                                                    gradeId:
                                                        e.target.value,
                                                })
                                            }
                                            className={
                                                inputClass
                                            }
                                        >
                                            <option value="">
                                                Pilih grade
                                            </option>

                                            {grades.map(
                                                (grade) => (
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
                                    </Field>
                                </div>


                                {/* MATERIALS */}

                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <div className="text-sm font-medium text-gray-700">
                                                Materials
                                            </div>

                                            <div className="mt-1 text-xs text-gray-500">
                                                Tambahkan satu atau beberapa material untuk produk ini.
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            onClick={
                                                addProductMaterial
                                            }
                                            className="inline-flex items-center gap-2 rounded-lg border border-[#19875f] px-3 py-2 text-sm font-medium text-[#19875f] transition hover:bg-emerald-50"
                                        >
                                            <Plus size={16} />

                                            Add Material
                                        </button>
                                    </div>


                                    {productForm.materials.length ===
                                        0 && (
                                            <div className="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-5 text-center text-sm text-gray-500">
                                                Belum ada material.
                                                Klik Add Material untuk menambahkan.
                                            </div>
                                        )}


                                    {productForm.materials.map(
                                        (
                                            selectedMaterial,
                                            index
                                        ) => (
                                            <div
                                                key={
                                                    index
                                                }
                                                className="flex items-center gap-3"
                                            >
                                                <div className="flex-1">
                                                    <select
                                                        value={
                                                            selectedMaterial
                                                        }
                                                        onChange={(
                                                            e
                                                        ) =>
                                                            updateProductMaterial(
                                                                index,
                                                                e.target
                                                                    .value
                                                            )
                                                        }
                                                        className={
                                                            inputClass
                                                        }
                                                    >
                                                        <option value="">
                                                            Pilih material
                                                        </option>

                                                        {materials
                                                            .filter(
                                                                (
                                                                    material
                                                                ) =>
                                                                    material.source_key !==
                                                                    `product:${editingProduct?.id}`
                                                            )
                                                            .map(
                                                                (
                                                                    material
                                                                ) => {
                                                                    /*
                                                                     * Jangan izinkan
                                                                     * source yang sudah
                                                                     * dipilih di row lain.
                                                                     */
                                                                    const alreadySelected =
                                                                        productForm.materials.some(
                                                                            (
                                                                                value,
                                                                                itemIndex
                                                                            ) =>
                                                                                itemIndex !==
                                                                                index &&
                                                                                value ===
                                                                                material.source_key
                                                                        );

                                                                    return (
                                                                        <option
                                                                            key={
                                                                                material.source_key
                                                                            }
                                                                            value={
                                                                                material.source_key
                                                                            }
                                                                            disabled={
                                                                                alreadySelected
                                                                            }
                                                                        >
                                                                            {
                                                                                material.name
                                                                            }

                                                                            {material.code
                                                                                ? ` - ${material.code}`
                                                                                : ''}
                                                                        </option>
                                                                    );
                                                                }
                                                            )}
                                                    </select>
                                                </div>

                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        removeProductMaterial(
                                                            index
                                                        )
                                                    }
                                                    className="rounded-lg border border-red-200 p-3 text-red-500 transition hover:bg-red-50 hover:text-red-700"
                                                    title="Hapus material"
                                                >
                                                    <Trash2
                                                        size={
                                                            17
                                                        }
                                                    />
                                                </button>
                                            </div>
                                        )
                                    )}
                                </div>
                            </>
                        )}

                        {activeTab ===
                            'competitor' && (
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
                                                className={
                                                    inputClass
                                                }
                                                placeholder="Contoh: Cahaya Pelita"
                                            />
                                        </Field>

                                        <Field label="Divisi">
                                            <select
                                                required
                                                value={
                                                    competitorForm.division
                                                }
                                                onChange={(e) =>
                                                    setCompetitorForm({
                                                        ...competitorForm,
                                                        division:
                                                            e.target
                                                                .value as Division,
                                                    })
                                                }
                                                className={
                                                    inputClass
                                                }
                                            >
                                                <option value="Industri">
                                                    Industri
                                                </option>

                                                <option value="SME">
                                                    SME
                                                </option>

                                                <option value="Low Cost">
                                                    Low Cost
                                                </option>

                                                <option value="All">
                                                    All
                                                </option>
                                            </select>
                                        </Field>
                                    </div>

                                    <Field label="Keterangan Kompetitor">
                                        <textarea
                                            rows={3}
                                            value={
                                                competitorForm.competitorNote
                                            }
                                            onChange={(e) =>
                                                setCompetitorForm({
                                                    ...competitorForm,
                                                    competitorNote:
                                                        e.target.value,
                                                })
                                            }
                                            className={
                                                inputClass
                                            }
                                            placeholder="Contoh: Competitor utama area Cikarang dan Jawa Barat"
                                        />
                                    </Field>

                                    <div className="grid gap-4 md:grid-cols-2">
                                        <Field label="Produk">
                                            <select
                                                required
                                                value={
                                                    competitorForm.productId
                                                }
                                                onChange={(e) =>
                                                    setCompetitorForm({
                                                        ...competitorForm,
                                                        productId:
                                                            e.target.value,
                                                    })
                                                }
                                                className={
                                                    inputClass
                                                }
                                            >
                                                <option
                                                    value=""
                                                    disabled
                                                >
                                                    Pilih produk
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

                                                            {product.code
                                                                ? ` - ${product.code}`
                                                                : ''}
                                                        </option>
                                                    )
                                                )}
                                            </select>
                                        </Field>

                                        <Field label="Harga Kompetitor">
                                            <input
                                                required
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={
                                                    competitorForm.price
                                                }
                                                onChange={(e) =>
                                                    setCompetitorForm({
                                                        ...competitorForm,
                                                        price:
                                                            e.target.value,
                                                    })
                                                }
                                                className={
                                                    inputClass
                                                }
                                                placeholder="125000"
                                            />
                                        </Field>
                                    </div>

                                    <Field label="Tanggal Harga">
                                        <input
                                            required
                                            type="date"
                                            value={competitorForm.date}
                                            onChange={(e) =>
                                                setCompetitorForm({
                                                    ...competitorForm,
                                                    date: e.target.value,
                                                })
                                            }
                                            className={inputClass}
                                        />
                                    </Field>

                                    <Field label="Keterangan Harga / Produk">
                                        <textarea
                                            rows={3}
                                            value={
                                                competitorForm.productNote
                                            }
                                            onChange={(e) =>
                                                setCompetitorForm({
                                                    ...competitorForm,
                                                    productNote:
                                                        e.target.value,
                                                })
                                            }
                                            className={
                                                inputClass
                                            }
                                            placeholder="Contoh: Harga untuk MOQ tertentu"
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