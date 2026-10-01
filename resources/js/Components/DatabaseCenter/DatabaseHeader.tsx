import {
    Building2,
    Package,
    Swords,
    Download,
    Plus,
    Upload,
} from 'lucide-react';

import type {
    DatabaseTab,
} from '../../types/databaseCenter';

interface Props {
    activeTab: DatabaseTab;
    onAdd: () => void;
    onImport?: () => void;
    onDownloadTemplate?: () => void;
}

const config = {
    customer: {
        title: 'Database Customer',
        description:
            'Data profil pelanggan, alamat pabrik/kantor, segmentasi industri makanan, dan level risiko.',
        button: 'Tambah Customer',
        icon: Building2,
    },

    product: {
        title: 'Database Produk',
        description:
            'Katalog master komoditas rempah, bubuk, biji-bijian, dan standard pricelist.',
        button: 'Tambah Produk',
        icon: Package,
    },

    competitor: {
        title: 'Database Kompetitor (Pesaing)',
        description:
            'Data harga pasar kompetitor pembanding. Item produk diambil langsung dari Database Produk.',
        button: 'Tambah Data Competitor',
        icon: Swords,
    },
};

export default function DatabaseHeader({
    activeTab,
    onAdd,
    onImport,
    onDownloadTemplate,
}: Props) {
    const item = config[activeTab];

    const Icon = item.icon;

    return (
        <div
            className="
                flex flex-col gap-4
                border-b border-gray-200
                pb-5
                xl:flex-row
                xl:items-start
                xl:justify-between
            "
        >
            <div>
                <div className="flex items-center gap-2">
                    <Icon
                        size={22}
                        className="text-[#19875f]"
                    />

                    <h1 className="text-[22px] font-bold text-gray-900">
                        {item.title}
                    </h1>
                </div>

                <p className="mt-1 text-[13px] text-gray-500">
                    {item.description}
                </p>
            </div>

            <div className="flex items-center gap-2">
                <button
                    type="button"
                    onClick={onDownloadTemplate}
                    className="
                        flex h-10 items-center gap-2
                        rounded-lg
                        border border-gray-300
                        bg-white
                        px-4
                        text-sm font-semibold
                        text-gray-700
                        shadow-sm
                        hover:bg-gray-50
                    "
                >
                    <Download size={16} />

                    Download Template
                </button>

                {activeTab === 'customer' && (
                    <button
                        type="button"
                        onClick={onImport}
                        className="
                            flex h-10 items-center gap-2
                            rounded-lg
                            border border-[#19875f]/30
                            bg-[#ecfdf5]
                            px-4
                            text-sm font-semibold
                            text-[#146e4e]
                            shadow-sm
                            hover:bg-[#dffaf0]
                        "
                    >
                        <Upload size={16} />

                        Import Excel
                    </button>
                )}

                <button
                    type="button"
                    onClick={onAdd}
                    className="
                        flex h-10 items-center gap-2
                        rounded-lg
                        bg-[#19875f]
                        px-4
                        text-sm font-semibold
                        text-white
                        shadow-sm
                        transition
                        hover:bg-[#146e4e]
                    "
                >
                    <Plus size={17} />

                    {item.button}
                </button>
            </div>
        </div>
    );
}