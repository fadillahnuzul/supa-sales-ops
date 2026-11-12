import { Head } from '@inertiajs/react';
import axios from 'axios';
import type { ChangeEvent } from 'react';
import { useCallback, useEffect, useId, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { CalendarDays, Search, ClipboardList, FileText, CheckCircle2, Plus, Printer, Save, Trash2, Download, Upload } from 'lucide-react';
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
    inquiry_id: number | null;
    customer_id?: number | null;
    pic_id?: number | null;
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
    inquiry_id: number | null;
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

export function newDirectJobOrder(): JobOrder {
    return {
        inquiry_id: null,
        pic_id: null,
        date: today(),
        so_no: '',
        po_no: '',
        customer_no: '',
        customer_name: '',
        customer_address: '',
        pic_name: '',
        shipping_date: null,
        details: [{ kg: '', product_code: '', product_name: '', packing: '', note: '' }],
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
    selectedKey: string | null;
    loading: boolean;
    openingId: number | null;
    canApprove: boolean;
    canCheck: boolean;
    isApprover: boolean;
    isAdmin: boolean;
    marked: number[];
    saving: boolean;
    currentPage: number;
    lastPage: number;
    total: number | null;
    onSelect: (row: JobOrderRow) => void;
    onMarkedChange: (ids: number[]) => void;
    onCheck: (ids: number[]) => void;
    onApprove: (ids: number[]) => void;
    onPageChange: (page: number) => void;
}

function JobOrderTable(props: TableProps) {
    const { rows, selectedKey, loading, openingId, canApprove, canCheck, isApprover, isAdmin, marked, saving, currentPage, lastPage, total, onSelect, onMarkedChange, onCheck, onApprove, onPageChange } = props;
    const pendingIds = rows.filter((r) => r.job_order_id != null && ((canCheck && r.status === 'pending') || (isApprover && r.status === 'checked'))).map((r) => Number(r.job_order_id));
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
                    <div className="flex gap-2">
                        {canCheck && <button type="button" disabled={saving || !marked.length} onClick={() => onCheck(marked)} className="rounded-lg bg-[#19875f] px-3 py-2 text-xs font-medium text-white hover:bg-[#146f4e] disabled:opacity-40">Check ({marked.length})</button>}
                        {isApprover && <button type="button" disabled={saving || !marked.length} onClick={() => onApprove(marked)} className="rounded-lg bg-[#19875f] px-3 py-2 text-xs font-medium text-white hover:bg-[#146f4e] disabled:opacity-40">Approve ({marked.length})</button>}
                    </div>
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
                            const rowKey = row.job_order_id !== null
                                ? `jo-${row.job_order_id}`
                                : `inquiry-${row.inquiry_id}`;
                            const rowOpeningId = row.job_order_id ?? row.inquiry_id;
                            return (
                                <tr key={rowKey} onClick={() => canOpen && onSelect(row)} className={`border-t border-gray-100 ${canOpen ? 'cursor-pointer hover:bg-emerald-50' : ''} ${selectedKey === rowKey ? 'bg-emerald-50' : ''}`}>
                                    {canApprove && <td className="p-3" onClick={(event) => event.stopPropagation()}><input aria-label={`Pilih JO ${row.so_no ?? row.inquiry_code}`} type="checkbox" disabled={!row.job_order_id || !((canCheck && row.status === 'pending') || (isApprover && row.status === 'checked'))} checked={marked.includes(rowId)} onChange={(event) => onMarkedChange(event.target.checked ? Array.from(new Set([...marked, rowId])) : marked.filter((id) => id !== rowId))} /></td>}
                                    <td className="p-3 font-medium text-gray-900">{row.so_no || '—'}</td>
                                    <td className="p-3">{row.po_no || '—'}</td>
                                    <td className="p-3 whitespace-nowrap">{String(row.inquiry_date ?? '').slice(0, 10)}</td>
                                    <td className="p-3">{row.customer_name ?? '—'}</td>
                                    <td className="p-3"><span className={`rounded-full px-2 py-1 text-[11px] ${row.status === 'approved' ? 'bg-green-50 text-green-700' : row.status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600'}`}>{row.status ?? 'Belum JO'}</span></td>
                                    <td className="p-3"><button type="button" disabled={!canOpen || openingId === rowOpeningId} className="whitespace-nowrap font-medium text-[#19875f] disabled:opacity-40" onClick={(event) => { event.stopPropagation(); onSelect(row); }}>{openingId === rowOpeningId ? 'Memuat...' : row.job_order_id ? 'Lihat Detail' : 'Buat JO'}</button></td>
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
    picOptions: { id: number; name: string }[];
    onError: (message: string) => void;
    isAdmin: boolean;
    canPrint: boolean;
    canCheck: boolean;
    isApprover: boolean;
    saving: boolean;
    onChange: (order: JobOrder) => void;
    onSave: () => void;
    onCheck: (ids: number[]) => void;
    onApprove: (ids: number[]) => void;
}

const fieldClass = 'mt-1.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#19875f] focus:ring-2 focus:ring-[#19875f]/10 disabled:bg-gray-50 disabled:text-gray-500';
const primaryButton = 'inline-flex items-center justify-center gap-2 rounded-lg bg-[#19875f] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#146f4e] disabled:opacity-40';
const fields = [
    ['date', 'Tanggal JO', 'date', 'always'],
    ['shipping_date', 'Hari / Tanggal Kirim', 'date', 'always'],
    ['so_no', 'SO No.', 'text', 'always'],
    ['po_no', 'PO No.', 'text', 'always'],
    ['pic_name', 'PIC Sales', 'text', 'inquiry'],
] as const;
const productFields = ['kg', 'product_code', 'product_name', 'packing', 'note'] as const;

interface CustomerOption {
    id: number;
    code: string | null;
    name: string | null;
    address: string | null;
}

interface CustomerSelectorProps {
    order: JobOrder;
    disabled: boolean;
    onChange: (order: JobOrder) => void;
    onError: (message: string) => void;
}

interface ProductOption {
    id: number;
    code: string | null;
    name: string;
}

interface ProductSelectorProps {
    item: JobOrderItem;
    disabled: boolean;
    onChange: (product: ProductOption | null) => void;
    onError: (message: string) => void;
}

function customerLabel(customer: Pick<CustomerOption, 'code' | 'name'>): string {
    return customer.code || customer.name
        ? `${customer.code || '-'} - ${customer.name || ''}`
        : '';
}

function productLabel(product: Pick<ProductOption, 'code' | 'name'>): string {
    return product.code ? `${product.code} - ${product.name}` : product.name;
}

function CustomerSelector({ order, disabled, onChange, onError }: CustomerSelectorProps) {
    const [query, setQuery] = useState(customerLabel({
        code: order.customer_no || null,
        name: order.customer_name || null,
    }));
    const [options, setOptions] = useState<CustomerOption[]>([]);
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!open) return;

        let cancelled = false;
        const timer = window.setTimeout(async () => {
            setLoading(true);
            try {
                const response = await axios.get<CustomerOption[]>('/job-orders/customers', {
                    params: { search: query },
                });
                if (!cancelled) setOptions(response.data);
            } catch (error) {
                if (!cancelled) onError(getApiError(error, 'Gagal mencari customer.'));
            } finally {
                if (!cancelled) setLoading(false);
            }
        }, 250);

        return () => {
            cancelled = true;
            window.clearTimeout(timer);
        };
    }, [open, query, onError]);

    useEffect(() => {
        setQuery(customerLabel({
            code: order.customer_no || null,
            name: order.customer_name || null,
        }));
    }, [order.id, order.inquiry_id]);

    function selectCustomer(customer: CustomerOption) {
        setQuery(customerLabel(customer));
        setOpen(false);
        onChange({
            ...order,
            customer_id: customer.id,
            customer_no: customer.code ?? '',
            customer_name: customer.name ?? '',
            customer_address: customer.address ?? '',
        });
    }

    return (
        <label className="relative block text-xs font-medium text-gray-600">
            Customer
            <input
                className={fieldClass}
                type="search"
                role="combobox"
                aria-label="Cari No. Customer atau Nama Customer"
                aria-expanded={open}
                aria-controls="job-order-customer-options"
                autoComplete="off"
                disabled={disabled}
                placeholder="Cari no atau nama customer..."
                value={query}
                onFocus={() => setOpen(true)}
                onBlur={() => window.setTimeout(() => setOpen(false), 150)}
                onChange={(event) => {
                    const value = event.target.value;
                    setQuery(value);
                    setOpen(true);
                    if (order.customer_id !== null) {
                        onChange({
                            ...order,
                            customer_id: null,
                            customer_no: '',
                            customer_name: '',
                            customer_address: '',
                        });
                    }
                }}
            />
            {open && !disabled && (
                <ul id="job-order-customer-options" role="listbox" className="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                    {loading && <li className="px-3 py-2 text-gray-500">Mencari customer...</li>}
                    {!loading && options.map((customer) => (
                        <li key={customer.id} role="option" aria-selected={customer.id === order.customer_id}>
                            <button type="button" className="w-full px-3 py-2 text-left text-gray-700 hover:bg-emerald-50" onMouseDown={(event) => event.preventDefault()} onClick={() => selectCustomer(customer)}>
                                <span className="font-medium">{customerLabel(customer)}</span>
                                {customer.address && <span className="mt-0.5 block truncate text-gray-500">{customer.address}</span>}
                            </button>
                        </li>
                    ))}
                    {!loading && options.length === 0 && <li className="px-3 py-2 text-gray-500">Customer tidak ditemukan.</li>}
                </ul>
            )}
        </label>
    );
}

function ProductSelector({ item, disabled, onChange, onError }: ProductSelectorProps) {
    const optionsId = useId();
    const inputRef = useRef<HTMLInputElement>(null);
    const [query, setQuery] = useState(item.product_code);
    const [options, setOptions] = useState<ProductOption[]>([]);
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [dropdownPosition, setDropdownPosition] = useState<{
        top: number;
        left: number;
        width: number;
        maxHeight: number;
    } | null>(null);

    useEffect(() => {
        if (!open) return;

        let cancelled = false;
        const timer = window.setTimeout(async () => {
            setLoading(true);
            try {
                const response = await axios.get<ProductOption[]>('/job-orders/products', {
                    params: { search: query },
                });
                if (!cancelled) setOptions(response.data);
            } catch (error) {
                if (!cancelled) onError(getApiError(error, 'Gagal mencari produk.'));
            } finally {
                if (!cancelled) setLoading(false);
            }
        }, 250);

        return () => {
            cancelled = true;
            window.clearTimeout(timer);
        };
    }, [open, query, onError]);

    useLayoutEffect(() => {
        if (!open) {
            setDropdownPosition(null);
            return;
        }

        function updateDropdownPosition() {
            const input = inputRef.current;
            if (!input) return;

            const bounds = input.getBoundingClientRect();
            const width = Math.min(Math.max(bounds.width, 280), window.innerWidth - 16);
            const spaceBelow = window.innerHeight - bounds.bottom - 8;

            setDropdownPosition({
                top: bounds.bottom + 4,
                left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
                width,
                maxHeight: Math.max(0, Math.min(256, spaceBelow)),
            });
        }

        updateDropdownPosition();
        window.addEventListener('resize', updateDropdownPosition);
        window.addEventListener('scroll', updateDropdownPosition, true);

        return () => {
            window.removeEventListener('resize', updateDropdownPosition);
            window.removeEventListener('scroll', updateDropdownPosition, true);
        };
    }, [open]);

    function selectProduct(product: ProductOption) {
        setQuery(product.code ?? '');
        setOpen(false);
        onChange(product);
    }

    return (
        <div>
            <input
                ref={inputRef}
                className="w-full rounded-md border border-gray-200 px-2 py-2 outline-none focus:border-[#19875f] disabled:bg-gray-50"
                type="search"
                role="combobox"
                aria-label="Cari kode atau nama barang"
                aria-expanded={open}
                aria-controls={optionsId}
                autoComplete="off"
                disabled={disabled}
                placeholder="Cari kode atau nama..."
                value={query}
                onFocus={() => setOpen(true)}
                onBlur={() => window.setTimeout(() => setOpen(false), 150)}
                onChange={(event) => {
                    setQuery(event.target.value);
                    setOpen(true);
                    onChange(null);
                }}
            />
            {open && !disabled && (
                dropdownPosition
                && typeof document !== 'undefined'
                && createPortal(
                    <ul
                        id={optionsId}
                        role="listbox"
                        className="overflow-y-auto rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                        style={{
                            position: 'fixed',
                            zIndex: 50,
                            top: dropdownPosition.top,
                            left: dropdownPosition.left,
                            width: dropdownPosition.width,
                            maxHeight: dropdownPosition.maxHeight,
                        }}
                    >
                        {loading && <li className="px-3 py-2 text-gray-500">Mencari produk...</li>}
                        {!loading && options.map((product) => (
                            <li key={product.id} role="option" aria-selected={product.code === item.product_code && product.name === item.product_name}>
                                <button type="button" className="w-full px-3 py-2 text-left text-gray-700 hover:bg-emerald-50" onMouseDown={(event) => event.preventDefault()} onClick={() => selectProduct(product)}>
                                    <span className="font-medium">{productLabel(product)}</span>
                                </button>
                            </li>
                        ))}
                        {!loading && options.length === 0 && <li className="px-3 py-2 text-gray-500">Produk tidak ditemukan.</li>}
                    </ul>,
                    document.body
                )
            )}
        </div>
    );
}

function JobOrderForm({ order, picOptions, onError, isAdmin, canPrint, canCheck, isApprover, saving, onChange, onSave, onCheck, onApprove }: FormProps) {
    if (!order) {
        return <div className="flex min-h-[320px] flex-col items-center justify-center p-10 text-center"><FileText size={38} className="mb-3 text-gray-300" /><p className="text-sm font-medium text-gray-700">Pilih Inquiry atau Job Order</p><p className="mt-1 text-sm text-gray-500">Detail akan tampil di sini.</p></div>;
    }
    const readonly = !isAdmin || order.status === 'approved';
    const updateField = <K extends keyof JobOrder>(key: K, value: JobOrder[K]) => onChange({ ...order, [key]: value });
    const updateProduct = (index: number, key: keyof JobOrderItem, value: string) => {
        const details = order.details.map((item, i) => i === index ? { ...item, [key]: value } : item);
        updateField('details', details);
    };
    const orderKey = order.id ?? order.inquiry_id ?? `${order.date}-${order.so_no}`;
    return (
        <div className="space-y-5 p-4 lg:p-5">
            {order.status === 'approved' && <div className="flex items-center gap-2 rounded-lg bg-green-50 px-3 py-3 text-sm text-green-700"><CheckCircle2 size={17} /> JO telah disetujui. Perubahan dikunci.</div>}
            <div>
                <h3 className="mb-3 text-sm font-semibold text-gray-900">Informasi Pesanan</h3>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {order.inquiry_id === null && (
                        <CustomerSelector
                            order={order}
                            disabled={readonly}
                            onChange={onChange}
                            onError={onError}
                        />
                    )}
                    {order.inquiry_id !== null && (
                        <label className="text-xs font-medium text-gray-600">
                            Customer
                            <input
                                className={fieldClass}
                                type="text"
                                disabled
                                value={order.customer_no
                                    ? `${order.customer_no} - ${order.customer_name}`
                                    : order.customer_name || '—'}
                            />
                        </label>
                    )}
                    {fields.map(([key, label, type, status]) => (
                        <label key={key} className="text-xs font-medium text-gray-600">
                            {label}
                            {key === 'pic_name' && order.inquiry_id === null && !readonly ? (
                                <select
                                    className={fieldClass}
                                    disabled={readonly}
                                    value={String(order.pic_id ?? '')}
                                    onChange={(event) => {
                                        const picId = event.target.value ? Number(event.target.value) : null;
                                        const pic = picOptions.find((option) => option.id === picId);
                                        onChange({ ...order, pic_id: picId, pic_name: pic?.name ?? '' });
                                    }}
                                >
                                    <option value="">Pilih PIC Sales</option>
                                    {picOptions.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}
                                </select>
                            ) : (
                                <input
                                    className={fieldClass}
                                    type={type}
                                    disabled={readonly || (status === 'inquiry' && order.inquiry_id !== null)}
                                    value={String(order[key] ?? '')}
                                    onChange={(e) => updateField(key, e.target.value)}
                                />
                            )}
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
                        {productFields.map((key) => (
                            <td key={key} className="p-1.5">
                                {key === 'product_code' ? (
                                    <ProductSelector
                                        key={`${orderKey}-${item.id ?? index}`}
                                        item={item}
                                        disabled={readonly}
                                        onChange={(product) => {
                                            const details = order.details.map((detail, detailIndex) => detailIndex === index
                                                ? { ...detail, product_code: product?.code ?? '', product_name: product?.name ?? '' }
                                                : detail);
                                            updateField('details', details);
                                        }}
                                        onError={onError}
                                    />
                                ) : key === 'product_name' ? (
                                    <input className="w-full rounded-md border border-gray-200 px-2 py-2 outline-none focus:border-[#19875f] disabled:bg-gray-50" disabled={readonly} type="text" value={item.product_name} onChange={(event) => updateProduct(index, 'product_name', event.target.value)} />
                                ) : (
                                    <input className="w-full rounded-md border border-gray-200 px-2 py-2 outline-none focus:border-[#19875f] disabled:bg-gray-50" disabled={readonly} type={key === 'kg' ? 'number' : 'text'} min={key === 'kg' ? 0 : undefined} step={key === 'kg' ? '0.1' : undefined} value={item[key] ?? ''} onChange={(e) => updateProduct(index, key, e.target.value)} />
                                )}
                            </td>
                        ))}
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
                {canPrint && Boolean(order.id) && (
                    <a href={jobOrderPrintUrl(order.id!)} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <Printer size={16} /> Print JO
                    </a>
                )}
                {canCheck && order.status === 'pending' && Boolean(order.id) && (
                    <button type="button" disabled={saving} onClick={() => onCheck([order.id!])} className={primaryButton}>
                        <CheckCircle2 size={16} /> Check JO
                    </button>
                )}
                {isApprover && order.status === 'checked' && Boolean(order.id) && (
                    <button type="button" disabled={saving} onClick={() => onApprove([order.id!])} className={primaryButton}>
                        <CheckCircle2 size={16} /> Approve JO
                    </button>
                )}
            </div>
        </div>
    );
}


// Pages/JobOrderCenter.tsx
interface PageProps { roleIds: number[]; picOptions: { id: number; name: string }[]; }

export default function JobOrderCenter({ roleIds, picOptions }: PageProps) {
    // Access must also be enforced by the Laravel controller.
    const isAdmin = roleIds.some((roleId) => [1, 4].includes(roleId));
    const isApprover = roleIds.includes(5);
    const canCheck = roleIds.some((roleId) => [2, 3].includes(roleId));
    const canPrint = roleIds.some((roleId) => [1, 2, 3, 4, 5].includes(roleId));
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
    const [notice, setNotice] = useState('');

    // DETAIL / APPROVAL
    const [selected, setSelected] = useState<JobOrder | null>(null);
    const [openingId, setOpeningId] = useState<number | null>(null);
    const [marked, setMarked] = useState<number[]>([]);
    const [saving, setSaving] = useState(false);
    const [refreshKey, setRefreshKey] = useState(0);
    const importInput = useRef<HTMLInputElement>(null);
    const selectedKey = selected?.id
        ? `jo-${selected.id}`
        : selected?.inquiry_id !== null && selected?.inquiry_id !== undefined
            ? `inquiry-${selected.inquiry_id}`
            : null;

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
        setOpeningId(row.job_order_id ?? row.inquiry_id);
        setError('');
        setNotice('');
        try { setSelected(await fetchJobOrder(row)); }
        catch (err) { setError(getApiError(err, 'Gagal membuka detail Job Order.')); }
        finally { setOpeningId(null); }
    }

    function handleCreateDirect() {
        setSelected(newDirectJobOrder());
        setError('');
        setNotice('');
    }

    async function handleImport(event: ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        setSaving(true);
        setError('');
        setNotice('');
        try {
            const response = await axios.post('/job-orders/bulk-import', formData);
            setNotice(response.data.message ?? 'Impor Job Order berhasil.');
            reload();
        } catch (err) {
            setError(getApiError(err, 'Gagal mengimpor Job Order.'));
        } finally {
            setSaving(false);
            event.target.value = '';
        }
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
                        {notice && <div role="status" className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{notice}</div>}
                        <div className="grid grid-cols-1 gap-5 xl:grid-cols-12">
                            <section className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-5">
                                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-4">
                                    <div className="flex items-center gap-2"><FileText size={18} className="text-[#19875f]" /><h2 className="font-semibold text-gray-900">{isAdmin ? 'Daftar Inquiry & JO' : 'Daftar Job Order'}</h2></div>
                                    {isAdmin && <div className="flex flex-wrap gap-2">
                                        <a href="/job-orders/template" className="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"><Download size={15} /> Template Excel</a>
                                        <button type="button" disabled={saving} onClick={() => importInput.current?.click()} className="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-40"><Upload size={15} /> Impor Excel</button>
                                        <input ref={importInput} type="file" accept=".csv,.xlsx" className="hidden" onChange={(event) => void handleImport(event)} />
                                        <button type="button" onClick={handleCreateDirect} className="inline-flex items-center gap-2 rounded-lg bg-[#19875f] px-3 py-2 text-xs font-medium text-white hover:bg-[#146f4e]"><Plus size={15} /> PO Baru</button>
                                    </div>}
                                    {isAdmin && <p className="w-full text-xs text-gray-500">Impor: satu baris per item; SO No. sama digabung. Gunakan tanggal YYYY-MM-DD dan nama PIC karyawan aktif (maks. 500 baris).</p>}
                                </div>
                                <JobOrderFilter startDate={startDate} endDate={endDate} search={search} onStartDateChange={setStartDate} onEndDateChange={setEndDate} onSearchChange={setSearch} />
                                <JobOrderTable rows={rows} selectedKey={selectedKey} loading={loading} openingId={openingId} isAdmin={isAdmin} canApprove={canApprove} canCheck={canCheck} isApprover={isApprover} marked={marked} saving={saving} currentPage={currentPage} lastPage={lastPage} total={total} onSelect={(row) => void handleSelect(row)} onMarkedChange={setMarked} onCheck={(ids) => void handleCheck(ids)} onApprove={(ids) => void handleApprove(ids)} onPageChange={setPage} />
                            </section>
                            <section className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm xl:col-span-7">
                                <div className="border-b border-gray-100 p-4"><h2 className="font-semibold text-gray-900">{selected?.id ? `Detail JO • ${selected.so_no || selected.id}` : selected?.inquiry_id === null ? 'Buat Job Order dari PO' : selected ? 'Buat Job Order dari Inquiry' : 'Detail Job Order'}</h2></div>
                                <JobOrderForm order={selected} picOptions={picOptions} onError={setError} isAdmin={isAdmin} canPrint={canPrint} canCheck={canCheck} isApprover={isApprover} saving={saving} onChange={setSelected} onSave={() => void handleSave()} onCheck={(ids) => void handleCheck(ids)} onApprove={(ids) => void handleApprove(ids)} />
                            </section>
                        </div>
                    </div>
                </div>
            </AuthenticatedLayout>
        </>
    );
}
