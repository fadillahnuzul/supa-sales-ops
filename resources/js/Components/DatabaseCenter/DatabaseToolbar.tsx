import {
    Search,
    ArrowUp,
} from 'lucide-react';

import type {
    DatabaseTab,
} from '../../types/databaseCenter';

interface Props {
    activeTab: DatabaseTab;
    search: string;
    setSearch: (value: string) => void;
}

export default function DatabaseToolbar({
    activeTab,
    search,
    setSearch,
}: Props) {
    function getPlaceholder() {
        switch (activeTab) {
            case 'customer':
                return 'Cari nama perusahaan, alamat, atau PIC...';

            case 'product':
                return 'Cari nama produk rempah atau kode barang...';

            case 'competitor':
                return 'Cari nama competitor atau komoditas produk...';
        }
    }

    return (
        <div
            className="
                rounded-xl
                border border-gray-200
                bg-white
                p-4
                shadow-sm
            "
        >
            <div
                className="
                    grid grid-cols-1 gap-3
                    lg:grid-cols-[1.5fr_0.9fr_1fr_auto]
                "
            >
                <div className="relative">
                    <Search
                        size={17}
                        className="
                            absolute
                            left-4 top-1/2
                            -translate-y-1/2
                            text-gray-400
                        "
                    />

                    <input
                        value={search}
                        onChange={(e) =>
                            setSearch(
                                e.target.value
                            )
                        }
                        placeholder={getPlaceholder()}
                        className="
                            h-11 w-full
                            rounded-lg
                            border border-gray-300
                            bg-white
                            pl-11 pr-4
                            text-sm
                            outline-none
                            focus:border-[#19875f]
                        "
                    />
                </div>

                <select
                    className="
                        h-11 rounded-lg
                        border border-gray-300
                        bg-white
                        px-3
                        text-sm
                        outline-none
                        focus:border-[#19875f]
                    "
                >
                    {activeTab === 'customer' && (
                        <>
                            <option>
                                Semua Segmentasi
                            </option>

                            <option>
                                General Food Processing
                            </option>

                            <option>
                                Snack Industri
                            </option>

                            <option>
                                HORECA
                            </option>
                        </>
                    )}

                    {activeTab === 'product' && (
                        <>
                            <option>
                                Semua Kategori Produk
                            </option>

                            <option>
                                Spices & Herbs
                            </option>

                            <option>
                                Whole Spices
                            </option>

                            <option>
                                Pepper
                            </option>
                        </>
                    )}

                    {activeTab ===
                        'competitor' && (
                        <>
                            <option>
                                Semua Nama Competitor
                            </option>

                            <option>
                                Cahaya Pelita
                            </option>

                            <option>
                                Duta Bumbu Sejahtera
                            </option>
                        </>
                    )}
                </select>

                <select
                    className="
                        h-11 rounded-lg
                        border border-gray-300
                        bg-white
                        px-3
                        text-sm
                        outline-none
                        focus:border-[#19875f]
                    "
                >
                    <option>
                        Sort: Nama (A-Z)
                    </option>

                    <option>
                        Sort: Nama (Z-A)
                    </option>
                </select>

                <button
                    type="button"
                    className="
                        flex h-11
                        items-center
                        justify-center
                        gap-1.5
                        rounded-lg
                        border border-gray-300
                        bg-white
                        px-3
                        text-xs font-bold
                    "
                >
                    <ArrowUp
                        size={14}
                        className="text-[#19875f]"
                    />

                    ASC
                </button>
            </div>
        </div>
    );
}