import { Head } from '@inertiajs/react';
import axios from 'axios';
import { useCallback, useEffect, useState } from 'react';
import { CalendarDays, Search, ClipboardList, FileText, CheckCircle2, Plus, Printer, Save, Trash2 } from 'lucide-react';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';

export interface JobOrderItem {
    id?: number;
    inquiry_detail_id?: number | null;
    kg: string;
    product_code: string;
    product_name: string;
    packing: string;
    note: string;
}

export interface JobOrder {
    id?: number;
    inquiry_id: number;
    date: string;
    so_no: string;
    po_no: string;
    customer_no: string;
    customer_name: string;
    customer_address: string;
    pic_name: string;
    shipping_date: string | null;
    status?: string;
    details: JobOrderItem[];
}

export interface JobOrderRow {
    inquiry_id: number;
    inquiry_date: string;
    inquiry_code: string;
    customer_name: string;
    job_order_id: number | null;
    so_no: string | null;
    po_no: string | null;
    status: string | null;
}

export interface ListFilters {
    start_date: string;
    end_date: string;
    search: string;
}

export interface JobOrderListResult {
    rows: JobOrderRow[];
    total: number | null;
    currentPage: number;
    lastPage: number;
}

export function localDate(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

export function today(): string { return localDate(new Date()); }
export function firstDayOfMonth(): string {
    const d = new Date();
    return localDate(new Date(d.getFullYear(), d.getMonth(), 1));
}
export function lastDayOfMonth(): string {
    const d = new Date();
    return localDate(new Date(d.getFullYear(), d.getMonth() + 1, 0));
}

export function normalizeJobOrder(data: JobOrder): JobOrder {
    return {
        ...data,
        date: String(data.date ?? today()).slice(0, 10),
        shipping_date: data.shipping_date ? String(data.shipping_date).slice(0, 10) : null,
        details: (data.details ?? []).map((detail) => ({
            ...detail,
            kg: detail.kg === '' || detail.kg == null ? '' : String(detail.kg),
            product_code: detail.product_code ?? '',
            product_name: detail.product_name ?? '',
            packing: detail.packing ?? '',
            note: detail.note ?? '',
        })),
    };
}

export function getApiError(error: unknown, fallback: string): string {
    if (!axios.isAxiosError(error)) return fallback;
    const validation = error.response?.data?.errors as Record<string, string[]> | undefined;
    if (validation) return Object.values(validation).flat().join(' • ');
    return error.response?.data?.message ?? fallback;
}

export async function fetchJobOrderList(filters: ListFilters, page = 1): Promise<JobOrderListResult> {
    const response = await axios.get('/job-orders/list', { params: { ...filters, page } });
    const payload = response.data;
    // Laravel paginator => {data: [...], current_page, last_page, total}
    // Legacy endpoint might return a plain array or {data: [...]}.
    const rows: JobOrderRow[] = Array.isArray(payload) ? payload :
        Array.isArray(payload?.data) ? payload.data :
            Array.isArray(payload?.data?.data) ? payload.data.data : [];
    const pagination = Array.isArray(payload?.data?.data) ? payload.data : payload;
    return {
        rows,
        total: typeof pagination?.total === 'number' ? pagination.total : null,
        currentPage: Number(pagination?.current_page ?? page),
        lastPage: Number(pagination?.last_page ?? 1),
    };
}

export async function fetchJobOrder(row: JobOrderRow): Promise<JobOrder> {
    const path = row.job_order_id
        ? `/job-orders/${row.job_order_id}`
        : `/job-orders/inquiry/${row.inquiry_id}`;
    const { data } = await axios.get<JobOrder>(path);
    return normalizeJobOrder(data);
}

export async function fetchJobOrderById(id: number): Promise<JobOrder> {
    const { data } = await axios.get<JobOrder>(`/job-orders/${id}`);
    return normalizeJobOrder(data);
}

export async function saveJobOrder(order: JobOrder): Promise<JobOrder> {
    const response = order.id
        ? await axios.put(`/job-orders/${order.id}`, order)
        : await axios.post('/job-orders', order);
    const id = Number(response.data.id ?? response.data.data?.id);
    if (!Number.isFinite(id) || id < 1) throw new Error('Respons simpan JO tidak memiliki ID.');
    return fetchJobOrderById(id);
}

export async function approveJobOrders(ids: number[]): Promise<void> {
    if (ids.length === 1) await axios.post(`/job-orders/${ids[0]}/approve`);
    else await axios.post('/job-orders/bulk-approve', { ids });
}

export async function checkJobOrders(ids: number[]): Promise<void> {
    if (ids.length === 1) await axios.post(`/job-orders/${ids[0]}/check`);
    else await axios.post('/job-orders/bulk-check', { ids });
}

export function jobOrderPrintUrl(id: number): string { return `/job-orders/${id}/print`; }


// Components/JobOrder/JobOrderFilter.tsx
interface FilterProps {
    startDate: string;
    endDate: string;
    search: string;
    onStartDateChange: (value: string) => void;
    onEndDateChange: (value: string) => void;
    onSearchChange: (value: string) => void;
}

const inputClass = 'mt-1.5 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm outline-none focus:border-[#19875f] focus:ring-2 focus:ring-[#19875f]/10';

function JobOrderFilter({ startDate, endDate, search, onStartDateChange, onEndDateChange, onSearchChange }: FilterProps) {
    return (
        <div className="space-y-3 border-b border-gray-100 p-4">
            <div className="flex items-center gap-2 text-sm font-semibold text-gray-800"><CalendarDays size={17} className="text-[#19875f]" /> Filter Job Order</div>
            <div className="grid grid-cols-2 gap-3">
                <label className="text-xs font-medium text-gray-600">Tanggal Awal
                    <input type="date" className={inputClass} value={startDate} max={endDate || undefined} onChange={(e) => onStartDateChange(e.target.value)} />
                </label>
                <label className="text-xs font-medium text-gray-600">Tanggal Akhir
                    <input type="date" className={inputClass} value={endDate} min={startDate || undefined} onChange={(e) => onEndDateChange(e.target.value)} />
                </label>
            </div>
            <label className="relative block">
                <Search size={16} className="absolute left-3 top-3 text-gray-400" />
                <input className="h-10 w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 text-sm outline-none focus:border-[#19875f]" value={search} onChange={(e) => onSearchChange(e.target.value)} placeholder="Cari SO, PO, customer, inquiry..." aria-label="Cari Job Order" />
            </label>
        </div>
    );
}


// Components/JobOrder/JobOrderTable.tsx
interface TableProps {
    rows: JobOrderRow[];
    selectedInquiryId: number | null;
    loading: boolean;
    openingId: number | null;
    canApprove: boolean;
    isApprover: boolean;
    isAdmin: boolean;
    marked: number[];
    saving: boolean;
    currentPage: number;
    lastPage: number;
    total: number | null;
    onSelect: (row: JobOrderRow) => void;
    onMarkedChange: (ids: number[]) => void;
    onAction: (ids: number[]) => void;
    onPageChange: (page: number) => void;
}

function JobOrderTable(props: TableProps) {
    const { rows, selectedInquiryId, loading, openingId, canApprove, isApprover, isAdmin, marked, saving, currentPage, lastPage, total, onSelect, onMarkedChange, onAction, onPageChange } = props;
    const pendingIds = rows.filter((r) => r.job_order_id != null && (isApprover ? ['pending', 'checked'].includes(r.status ?? '') : r.status === 'pending')).map((r) => Number(r.job_order_id));
    const allChecked = pendingIds.length > 0 && pendingIds.every((id) => marked.includes(id));
    const columnCount = canApprove ? 7 : 6;
    return (
        <>
            {canApprove && (
                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3">
                    <label className="flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" checked={allChecked} disabled={!pendingIds.length} onChange={(e) => onMarkedChange(e.target.checked ? Array.from(new Set([...marked, ...pendingIds])) : marked.filter((id) => !pendingIds.includes(id)))} />
                        Pilih semua pending pada halaman ini
                    </label>
                    <button type="button" disabled={saving || !marked.length} onClick={() => onAction(marked)} className="rounded-lg bg-[#19875f] px-3 py-2 text-xs font-medium text-white hover:bg-[#146f4e] disabled:opacity-40">{isApprover ? 'Approve' : 'Check'} ({marked.length})</button>
                </div>
            )}
            <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                    <thead className="bg-gray-50 text-gray-600"><tr>
                        {canApprove && <th className="p-3">✓</th>}
                        {['SO', 'PO', 'Tanggal', 'Customer', 'Status', 'Aksi'].map((heading) => <th key={heading} className="p-3 font-semibold">{heading}</th>)}
                    </tr></thead>
                    <tbody>
                        {rows.map((row) => {
                            const canOpen = isAdmin || row.job_order_id != null;
                            const rowId = Number(row.job_order_id);
                            return (
                                <tr key={row.inquiry_id} onClick={() => canOpen && onSelect(row)} className={`border-t border-gray-100 ${canOpen ? 'cursor-pointer hover:bg-emerald-50' : ''} ${selectedInquiryId === row.inquiry_id ? 'bg-emerald-50' : ''}`}>
                                    {canApprove && <td className="p-3" onClick={(event) => event.stopPropagation()}><input aria-label={`Pilih JO ${row.so_no ?? row.inquiry_code}`} type="checkbox" disabled={!row.job_order_id || !(isApprover ? ['pending', 'checked'].includes(row.status ?? '') : row.status === 'pending')} checked={marked.includes(rowId)} onChange={(event) => onMarkedChange(event.target.checked ? Array.from(new Set([...marked, rowId])) : marked.filter((id) => id !== rowId))} /></td>}
                                    <td className="p-3 font-medium text-gray-900">{row.so_no || '—'}</td>
                                    <td className="p-3">{row.po_no || '—'}</td>
                                    <td className="p-3 whitespace-nowrap">{String(row.inquiry_date ?? '').slice(0, 10)}</td>
                                    <td className="p-3">{row.customer_name ?? '—'}</td>
                                    <td className="p-3"><span className={`rounded-full px-2 py-1 text-[11px] ${row.status === 'approved' ? 'bg-green-50 text-green-700' : row.status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600'}`}>{row.status ?? 'Belum JO'}</span></td>
                                    <td className="p-3"><button type="button" disabled={!canOpen || openingId === row.inquiry_id} className="whitespace-nowrap font-medium text-[#19875f] disabled:opacity-40" onClick={(event) => { event.stopPropagation(); onSelect(row); }}>{openingId === row.inquiry_id ? 'Memuat...' : row.job_order_id ? 'Lihat Detail' : 'Buat JO'}</button></td>
                                </tr>
                            );
                        })}
                        {!rows.length && <tr><td colSpan={columnCount} className="p-10 text-center text-sm text-gray-500">{loading ? 'Memuat data...' : 'Tidak ada data pada rentang tanggal ini.'}</td></tr>}
                    </tbody>
                </table>
            </div>
            <div className="flex items-center justify-between gap-3 border-t border-gray-100 px-4 py-3 text-xs text-gray-500">
                <span>{total === null ? `${rows.length} data` : `${total} data`} • Halaman {currentPage} dari {lastPage}</span>
                <div className="flex gap-2">
                    <button type="button" className="rounded-lg border px-3 py-1.5 disabled:opacity-40" disabled={loading || currentPage <= 1} onClick={() => onPageChange(currentPage - 1)}>Sebelumnya</button>
                    <button type="button" className="rounded-lg border px-3 py-1.5 disabled:opacity-40" disabled={loading || currentPage >= lastPage} onClick={() => onPageChange(currentPage + 1)}>Berikutnya</button>
                </div>
            </div>
        </>
    );
}


// Components/JobOrder/JobOrderForm.tsx
interface FormProps {
    order: JobOrder | null;
    isAdmin: boolean;
    canApprove: boolean;
    isApprover: boolean;
    saving: boolean;
    onChange: (order: JobOrder) => void;
    onSave: () => void;
    onAction: (ids: number[]) => void;
}

const fieldClass = 'mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#19875f] focus:ring-2 focus:ring-[#19875f]/10 disabled:bg-gray-50 disabled:text-gray-500';
const primaryButton = 'inline-flex items-center justify-center gap-2 rounded-lg bg-[#19875f] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#146f4e] disabled:opacity-40';
const fields = [
    ['date', 'Tanggal JO', 'date', 'enabled'],
    ['shipping_date', 'Hari / Tanggal Kirim', 'date', 'enabled'],
    ['so_no', 'SO No.', 'text', 'enabled'],
    ['po_no', 'PO No.', 'text', 'enabled'],
    ['customer_no', 'No. Customer', 'text', 'disabled'],
    ['customer_name', 'Nama Customer', 'text', 'disabled'],
    ['pic_name', 'PIC Sales', 'text', 'disabled'],
] as const;
const productFields = ['kg', 'product_code', 'product_name', 'packing', 'note'] as const;

function JobOrderForm({ order, isAdmin, canApprove, isApprover, saving, onChange, onSave, onAction }: FormProps) {
    if (!order) {
        return <div className="flex min-h-[320px] flex-col items-center justify-center p-10 text-center"><FileText size={38} className="mb-3 text-gray-300" /><p className="text-sm font-medium text-gray-700">Pilih Inquiry atau Job Order</p><p className="mt-1 text-sm text-gray-500">Detail akan tampil di sini.</p></div>;
    }
    const readonly = !isAdmin || order.status === 'approved';
    const updateField = <K extends keyof JobOrder>(key: K, value: JobOrder[K]) => onChange({ ...order, [key]: value });
    const updateProduct = (index: number, key: keyof JobOrderItem, value: string) => {
        const details = order.details.map((item, i) => i === index ? { ...item, [key]: value } : item);
        updateField('details', details);
    };
    return (
        <div className="space-y-5 p-4 lg:p-5">
            {order.status === 'approved' && <div className="flex items-center gap-2 rounded-lg bg-green-50 px-3 py-3 text-sm text-green-700"><CheckCircle2 size={17} /> JO telah disetujui. Perubahan dikunci.</div>}
            <div>
                <h3 className="mb-3 text-sm font-semibold text-gray-900">Informasi Pesanan</h3>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {fields.map(([key, label, type, status]) => (
                        <label key={key} className="text-xs font-medium text-gray-600">
                            {label}
                            <input
                                className={fieldClass}
                                type={type}
                                disabled={readonly || status === 'disabled'}
                                value={String(order[key] ?? '')}
                                onChange={(e) => updateField(key, e.target.value)}
                            />
                        </label>
                    ))}
                </div>
                <label className="mt-4 block text-xs font-medium text-gray-600">Alamat Customer
                    <textarea className={fieldClass} rows={3} disabled={readonly} value={order.customer_address ?? ''} onChange={(e) => updateField('customer_address', e.target.value)} />
                </label>
            </div>
            <div>
                <div className="mb-3 flex items-center justify-between gap-3"><h3 className="text-sm font-semibold text-gray-900">Detail Produk</h3><span className="text-xs text-gray-500">{order.details.length} item</span></div>
                <div className="overflow-x-auto rounded-lg border border-gray-200"><table className="w-full min-w-[650px] text-xs">
                    <thead className="bg-gray-50 text-gray-600"><tr>{['KG', 'Kode Barang', 'Nama Barang', 'Packing', 'Keterangan'].map((x) => <th key={x} className="p-2 text-left">{x}</th>)}{!readonly && <th className="p-2">Aksi</th>}</tr></thead>
                    <tbody>{order.details.map((item, index) => <tr key={item.id ?? `new-${index}`} className="border-t border-gray-100">
                        {productFields.map((key) => <td key={key} className="p-1.5"><input className="w-full rounded-md border border-gray-200 px-2 py-2 outline-none focus:border-[#19875f] disabled:bg-gray-50" disabled={readonly} type={key === 'kg' ? 'number' : 'text'} min={key === 'kg' ? 0 : undefined} step={key === 'kg' ? '1' : undefined} value={item[key] ?? ''} onChange={(e) => updateProduct(index, key, e.target.value)} /></td>)}
                        {!readonly && <td className="p-2 text-center"><button type="button" aria-label={`Hapus produk ${index + 1}`} onClick={() => updateField('details', order.details.filter((_, i) => i !== index))} className="text-red-600 hover:text-red-700"><Trash2 size={16} /></button></td>}
                    </tr>)}</tbody>
                </table></div>
                {!readonly && <button type="button" onClick={() => updateField('details', [...order.details, { kg: '', product_code: '', product_name: '', packing: '', note: '' }])} className="mt-3 inline-flex items-center gap-2 text-sm font-medium text-[#19875f]"><Plus size={16} /> Tambah Produk</button>}
            </div>
            {/* Tombol aksi JO: tetap terlihat sesuai role dan status, termasuk saat approved. */}
            <div className="flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                {isAdmin && !order.id && (
                    <button type="button" disabled={saving} onClick={onSave} className={primaryButton}>
                        <Save size={16} /> {saving ? 'Menyimpan...' : 'Save / Buat JO'}
                    </button>
                )}
                {isAdmin && Boolean(order.id) && (
                    <button type="button" disabled={saving || readonly} onClick={onSave} className={primaryButton} title={readonly ? 'JO sudah approved dan tidak dapat diubah' : 'Simpan perubahan JO'}>
                        <Save size={16} /> {saving ? 'Menyimpan...' : 'Update JO'}
                    </button>
                )}
                {isAdmin && Boolean(order.id) && (
                    <a href={jobOrderPrintUrl(order.id!)} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <Printer size={16} /> Print JO
                    </a>
                )}
                {canApprove && Boolean(order.id) && (isApprover ? ['pending', 'checked'].includes(order.status ?? '') : order.status === 'pending') && (
                    <button type="button" disabled={saving} onClick={() => onAction([order.id!])} className={primaryButton}>
                        <CheckCircle2 size={16} /> {isApprover ? 'Approve JO' : 'Check JO'}
                    </button>
                )}
            </div>
        </div>
    );
}


// Pages/JobOrderCenter.tsx
interface PageProps { roleId: number; }

export default function JobOrderCenter({ roleId }: PageProps) {
    // Access must also be enforced by the Laravel controller.
    const isAdmin = roleId === 4 || roleId === 1;
    const isApprover = roleId === 5;
    const canCheck = roleId === 2 || roleId === 3;
    const canApprove = canCheck || isApprover;

    // FILTER
    const [startDate, setStartDate] = useState(firstDayOfMonth);
    const [endDate, setEndDate] = useState(lastDayOfMonth);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);

    // LIST
    const [rows, setRows] = useState<JobOrderRow[]>([]);
    const [total, setTotal] = useState<number | null>(null);
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    // DETAIL / APPROVAL
    const [selected, setSelected] = useState<JobOrder | null>(null);
    const [openingId, setOpeningId] = useState<number | null>(null);
    const [marked, setMarked] = useState<number[]>([]);
    const [saving, setSaving] = useState(false);
    const [refreshKey, setRefreshKey] = useState(0);

    useEffect(() => { setPage(1); setMarked([]); }, [startDate, endDate, search]);

    // Fetch list only. No business logic mixed with JSX.
    useEffect(() => {
        let cancelled = false;
        if (!startDate || !endDate || startDate > endDate) {
            setRows([]);
            setError('Rentang tanggal tidak valid.');
            return;
        }
        const timer = window.setTimeout(async () => {
            setLoading(true);
            setError('');
            try {
                const result = await fetchJobOrderList({ start_date: startDate, end_date: endDate, search }, page);
                if (!cancelled) {
                    setRows(result.rows);
                    setTotal(result.total);
                    setCurrentPage(result.currentPage);
                    setLastPage(result.lastPage);
                }
            } catch (err) {
                if (!cancelled) { setRows([]); setError(getApiError(err, 'Gagal memuat daftar Job Order.')); }
            } finally { if (!cancelled) setLoading(false); }
        }, 300);
        return () => { cancelled = true; window.clearTimeout(timer); };
    }, [startDate, endDate, search, page, refreshKey]);

    const reload = useCallback(() => setRefreshKey((value) => value + 1), []);

    async function handleSelect(row: JobOrderRow) {
        if (!isAdmin && row.job_order_id == null) return;
        setOpeningId(row.inquiry_id);
        setError('');
        try { setSelected(await fetchJobOrder(row)); }
        catch (err) { setError(getApiError(err, 'Gagal membuka detail Job Order.')); }
        finally { setOpeningId(null); }
    }

    async function handleSave() {
        if (!selected || !isAdmin || selected.status === 'approved') return;
        setSaving(true); setError('');
        try {
            setSelected(await saveJobOrder(selected));
            reload();
        } catch (err) { setError(getApiError(err, 'Gagal menyimpan JO.')); }
        finally { setSaving(false); }
    }

    async function handleCheck(ids: number[]) {
        if (!canCheck || !ids.length || saving) return;
        if (!window.confirm(`Check ${ids.length} Job Order?`)) return;
        setSaving(true); setError('');
        try {
            await checkJobOrders(ids);
            setMarked([]);
            if (selected?.id && ids.includes(selected.id)) setSelected(await fetchJobOrderById(selected.id));
            reload();
        } catch (err) { setError(getApiError(err, 'Check gagal.')); }
        finally { setSaving(false); }
    }

    async function handleApprove(ids: number[]) {
        if (!isApprover || !ids.length || saving) return;
        if (!window.confirm(`Approve ${ids.length} Job Order?`)) return;
        setSaving(true); setError('');
        try {
            await approveJobOrders(ids);
            setMarked([]);
            if (selected?.id && ids.includes(selected.id)) setSelected(await fetchJobOrderById(selected.id));
            reload();
        } catch (err) { setError(getApiError(err, 'Approval gagal.')); }
        finally { setSaving(false); }
    }

    const handleAction = isApprover ? handleApprove : handleCheck;

    return (
        <><Head title="Job Order" />
            <AuthenticatedLayout>
                <div className="min-h-full bg-[#f6f7f8] p-2 lg:p-3">
                    <div className="mx-auto max-w-[1800px] space-y-3">
                        <header className="flex items-center gap-3">
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#19875f]/10 text-[#19875f]"><ClipboardList size={22} /></div>
                            <div><h1 className="text-2xl font-semibold text-gray-900">Job Order</h1><p className="mt-1 text-sm text-gray-500">Pesanan Penjualan • PT SUPA SURYA NIAGA</p></div>
                        </header>
                        {error && <div role="alert" className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error}</div>}
                        <div className="grid grid-cols-1 gap-5 xl:grid-cols-12">
                            <section className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-5">
                                <div className="flex items-center gap-2 border-b border-gray-100 p-4"><FileText size={18} className="text-[#19875f]" /><h2 className="font-semibold text-gray-900">{isAdmin ? 'Daftar Inquiry & JO' : 'Daftar Job Order'}</h2></div>
                                <JobOrderFilter startDate={startDate} endDate={endDate} search={search} onStartDateChange={setStartDate} onEndDateChange={setEndDate} onSearchChange={setSearch} />
                                <JobOrderTable rows={rows} selectedInquiryId={selected?.inquiry_id ?? null} loading={loading} openingId={openingId} isAdmin={isAdmin} canApprove={canApprove} isApprover={isApprover} marked={marked} saving={saving} currentPage={currentPage} lastPage={lastPage} total={total} onSelect={(row) => void handleSelect(row)} onMarkedChange={setMarked} onAction={(ids) => void handleAction(ids)} onPageChange={setPage} />
                            </section>
                            <section className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-7">
                                <div className="border-b border-gray-100 p-4"><h2 className="font-semibold text-gray-900">{selected?.id ? `Detail JO • ${selected.so_no || selected.id}` : selected ? 'Buat Job Order dari Inquiry' : 'Detail Job Order'}</h2><p className="mt-1 text-xs text-gray-500"></p></div>
                                <JobOrderForm order={selected} isAdmin={isAdmin} canApprove={canApprove} isApprover={isApprover} saving={saving} onChange={setSelected} onSave={() => void handleSave()} onAction={(ids) => void handleAction(ids)} />
                            </section>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        </>
    );
}

