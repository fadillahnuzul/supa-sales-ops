interface Props {
    startDate: string; endDate: string; search: string;
    onStartDateChange: (value: string) => void;
    onEndDateChange: (value: string) => void;
    onSearchChange: (value: string) => void;
}

export default function InquiryPrintFilter(props: Props) {
    const inputClass = 'mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm';
    return <div className="grid gap-4 rounded-xl border border-gray-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-3">
        <label className="text-sm font-medium text-gray-700">Tanggal Awal
            <input type="date" required value={props.startDate} max={props.endDate || undefined} onChange={e => props.onStartDateChange(e.target.value)} className={inputClass} />
        </label>
        <label className="text-sm font-medium text-gray-700">Tanggal Akhir
            <input type="date" required value={props.endDate} min={props.startDate || undefined} onChange={e => props.onEndDateChange(e.target.value)} className={inputClass} />
        </label>
        <label className="text-sm font-medium text-gray-700">Cari Inquiry
            <input type="search" value={props.search} onChange={e => props.onSearchChange(e.target.value)} placeholder="Cari inquiry atau customer…" className={inputClass} />
        </label>
    </div>;
}