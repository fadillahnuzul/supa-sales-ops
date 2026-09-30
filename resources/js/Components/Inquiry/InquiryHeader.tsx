import { ClipboardList, Plus } from 'lucide-react';

interface Props {
    onAdd: () => void;
}

export default function InquiryHeader({
    onAdd,
}: Props) {
    return (
        <div className="flex flex-col gap-4 border-b border-gray-200 pb-5 md:flex-row md:items-start md:justify-between">
            <div>
                <div className="flex items-center gap-2">
                    <ClipboardList
                        size={22}
                        className="text-[#19875f]"
                    />

                    <h1 className="text-[22px] font-bold text-gray-900">
                        Inquiry
                    </h1>
                </div>

                <p className="mt-1 text-[13px] text-gray-500">
                    Kelola inquiry customer dan detail harga penawaran.
                </p>
            </div>

            <button
                type="button"
                onClick={onAdd}
                className="
                    flex h-10 items-center gap-2
                    rounded-lg bg-[#19875f]
                    px-4 text-sm font-semibold text-white
                    shadow-sm transition
                    hover:bg-[#146e4e]
                "
            >
                <Plus size={17} />

                Tambah Inquiry
            </button>
        </div>
    );
}