import { Head, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import { LoaderCircle, PenLine, Pencil, Upload } from 'lucide-react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    fetchSalesSignatures,
    updateSalesSignature,
    uploadSalesSignature,
} from '@/Services/InquiryPrintApi';
import type { SalesSignature } from '@/Services/InquiryPrintApi';

interface Signature {
    name: string;
    position: string | null;
    imageUrl: string;
}

interface SignatureSettingsProps {
    signature: Signature | null;
}

interface SignatureUploadForm {
    userId: string;
    name: string;
    division: string;
    position: string;
    file: File | null;
}

interface SignatureEditForm extends Omit<SignatureUploadForm, 'userId'> {
    id: number;
}

export default function SignatureSettings({ signature }: SignatureSettingsProps) {
    const [successMessage, setSuccessMessage] = useState('');
    const [salesSignatures, setSalesSignatures] = useState<SalesSignature[]>([]);
    const [signaturesLoading, setSignaturesLoading] = useState(true);
    const [signaturesError, setSignaturesError] = useState('');
    const [uploadingForSales, setUploadingForSales] = useState(false);
    const [editingSignature, setEditingSignature] = useState<SignatureEditForm | null>(null);
    const [savingEdit, setSavingEdit] = useState(false);
    const [uploadForm, setUploadForm] = useState<SignatureUploadForm>({
        userId: '',
        name: '',
        division: '',
        position: '',
        file: null,
    });
    const { data, setData, post, processing, errors, progress, reset } = useForm<{
        signature: File | null;
    }>({
        signature: null,
    });

    async function refreshSalesSignatures() {
        setSignaturesLoading(true);

        try {
            setSalesSignatures(await fetchSalesSignatures());
            setSignaturesError('');
        } catch (error) {
            console.error('Gagal memuat daftar signature sales:', error);
            setSignaturesError('Daftar signature gagal dimuat. Silakan coba muat ulang halaman.');
        } finally {
            setSignaturesLoading(false);
        }
    }

    useEffect(() => {
        void refreshSalesSignatures();
    }, []);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setSuccessMessage('');

        post(route('signature-settings.store'), {
            forceFormData: true,
            onSuccess: () => {
                reset('signature');
                setSuccessMessage('Tanda tangan berhasil diunggah.');
            },
        });
    }

    async function uploadSalesSignatureForEmployee(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!uploadForm.userId || !uploadForm.name.trim() || !uploadForm.file) {
            return;
        }

        setUploadingForSales(true);

        try {
            await uploadSalesSignature({
                user_id: Number(uploadForm.userId),
                name: uploadForm.name.trim(),
                division: uploadForm.division,
                position: uploadForm.position,
                signature: uploadForm.file,
            });
            setUploadForm({
                userId: '',
                name: '',
                division: '',
                position: '',
                file: null,
            });
            await refreshSalesSignatures();
        } catch (error) {
            console.error('Gagal mengunggah signature sales:', error);
            setSignaturesError('Signature gagal diunggah. Periksa file dan coba lagi.');
        } finally {
            setUploadingForSales(false);
        }
    }

    async function saveSignatureChanges(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!editingSignature || !editingSignature.name.trim()) {
            return;
        }

        setSavingEdit(true);

        try {
            const updatedSignature = await updateSalesSignature(editingSignature.id, {
                name: editingSignature.name.trim(),
                division: editingSignature.division,
                position: editingSignature.position,
                signature: editingSignature.file ?? undefined,
            });
            setSalesSignatures((items) => items.map((item) =>
                item.id === updatedSignature.id ? updatedSignature : item
            ));
            setEditingSignature(null);
            setSignaturesError('');
        } catch (error) {
            console.error('Gagal memperbarui signature sales:', error);
            setSignaturesError('Perubahan signature gagal disimpan. Silakan coba lagi.');
        } finally {
            setSavingEdit(false);
        }
    }

    return (
        <>
            <Head title="Tanda Tangan" />

            <AuthenticatedLayout>
                <div className="p-4 md:p-6">
                    <section className="mx-auto max-w-3xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="rounded-lg bg-green-50 p-2 text-[#19875f]">
                                <PenLine size={22} />
                            </span>
                            <div>
                                <h1 className="text-xl font-semibold text-gray-900">
                                    Tanda Tangan
                                </h1>
                                <p className="mt-1 text-sm text-gray-500">
                                    Unggah tanda tangan pribadi untuk digunakan pada dokumen.
                                </p>
                            </div>
                        </div>

                        {signature && (
                            <div className="mt-6 rounded-lg border border-gray-200 p-4">
                                <p className="text-sm font-medium text-gray-700">
                                    Tanda tangan aktif
                                </p>
                                <img
                                    src={signature.imageUrl}
                                    alt={`Tanda tangan ${signature.name}`}
                                    className="mt-3 h-28 w-full rounded-md bg-gray-50 object-contain p-2"
                                />
                                <p className="mt-2 text-sm font-semibold text-gray-900">
                                    {signature.name}
                                </p>
                                {signature.position && (
                                    <p className="mt-1 text-xs text-gray-500">
                                        {signature.position}
                                    </p>
                                )}
                            </div>
                        )}

                        <form onSubmit={submit} className="mt-6 space-y-4">
                            <label className="block">
                                <span className="text-sm font-medium text-gray-700">
                                    File tanda tangan
                                </span>
                                <input
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    onChange={(event) =>
                                        setData('signature', event.target.files?.[0] ?? null)
                                    }
                                    className="mt-2 block w-full rounded-lg border border-gray-300 p-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-green-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-green-700"
                                />
                            </label>

                            <p className="text-xs text-gray-500">
                                Format PNG, JPG, atau WEBP, maksimal 2 MB.
                            </p>

                            {errors.signature && (
                                <p role="alert" className="text-sm text-red-600">
                                    {errors.signature}
                                </p>
                            )}
                            {successMessage && (
                                <p role="status" className="text-sm text-green-700">
                                    {successMessage}
                                </p>
                            )}
                            {progress && (
                                <progress
                                    value={progress.percentage}
                                    max="100"
                                    className="w-full"
                                />
                            )}

                            <button
                                type="submit"
                                disabled={processing || !data.signature}
                                className="inline-flex items-center gap-2 rounded-lg bg-[#19875f] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#146d4c] disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <Upload size={17} />
                                {processing ? 'Mengunggah...' : 'Simpan Tanda Tangan'}
                            </button>
                        </form>
                    </section>

                    <section className="mx-auto mt-6 max-w-5xl rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900">
                                    Daftar Signature Sales
                                </h2>
                                <p className="mt-1 text-sm text-gray-500">
                                    Kelola data dan file TTD karyawan role sales serta PIC inquiry.
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={() => void refreshSalesSignatures()}
                                disabled={signaturesLoading}
                                className="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                            >
                                {signaturesLoading && <LoaderCircle size={16} className="animate-spin" />}
                                Muat ulang
                            </button>
                        </div>

                        {signaturesError && (
                            <p role="alert" className="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                                {signaturesError}
                            </p>
                        )}

                        <form onSubmit={uploadSalesSignatureForEmployee} className="mt-6 grid gap-4 rounded-lg bg-gray-50 p-4 md:grid-cols-2">
                            <h3 className="text-sm font-semibold text-gray-800 md:col-span-2">
                                Upload signature sales
                            </h3>
                            <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                Sales / PIC
                                <select
                                    required
                                    value={uploadForm.userId}
                                    onChange={(event) => {
                                        const selected = salesSignatures.find(
                                            (item) => String(item.user_id) === event.target.value
                                        );
                                        setUploadForm((form) => ({
                                            ...form,
                                            userId: event.target.value,
                                            name: selected?.name ?? '',
                                            division: selected?.division ?? '',
                                            position: selected?.position ?? '',
                                        }));
                                    }}
                                    className="h-10 rounded-lg border border-gray-300 bg-white px-3 font-normal"
                                >
                                    <option value="">Pilih sales / PIC</option>
                                    {salesSignatures
                                        .filter((item) => item.user_id !== null)
                                        .map((item) => (
                                            <option key={item.user_id} value={item.user_id}>
                                                {item.name}
                                                {item.id !== null ? ' (signature tersedia)' : ''}
                                            </option>
                                        ))}
                                </select>
                            </label>

                            <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                Nama
                                <input
                                    required
                                    maxLength={150}
                                    value={uploadForm.name}
                                    onChange={(event) => setUploadForm((form) => ({ ...form, name: event.target.value }))}
                                    className="h-10 rounded-lg border border-gray-300 bg-white px-3 font-normal"
                                />
                            </label>
                            <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                Divisi
                                <input
                                    maxLength={50}
                                    value={uploadForm.division}
                                    onChange={(event) => setUploadForm((form) => ({ ...form, division: event.target.value }))}
                                    className="h-10 rounded-lg border border-gray-300 bg-white px-3 font-normal"
                                />
                            </label>
                            <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                Posisi
                                <input
                                    maxLength={150}
                                    value={uploadForm.position}
                                    onChange={(event) => setUploadForm((form) => ({ ...form, position: event.target.value }))}
                                    className="h-10 rounded-lg border border-gray-300 bg-white px-3 font-normal"
                                />
                            </label>
                            <label className="grid gap-1.5 text-sm font-medium text-gray-700 md:col-span-2">
                                File TTD
                                <input
                                    required
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    onChange={(event) => setUploadForm((form) => ({
                                        ...form,
                                        file: event.target.files?.[0] ?? null,
                                    }))}
                                    className="rounded-lg border border-gray-300 bg-white p-2 text-sm font-normal file:mr-3 file:rounded-md file:border-0 file:bg-green-50 file:px-3 file:py-2 file:font-medium file:text-green-700"
                                />
                                <span className="text-xs font-normal text-gray-500">PNG, JPG, atau WEBP; maksimal 2 MB.</span>
                            </label>
                            <div className="md:col-span-2">
                                <button
                                    type="submit"
                                    disabled={uploadingForSales || signaturesLoading || !uploadForm.userId || !uploadForm.file}
                                    className="inline-flex items-center gap-2 rounded-lg bg-[#19875f] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#146d4c] disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <Upload size={16} />
                                    {uploadingForSales ? 'Mengunggah...' : 'Upload Signature Sales'}
                                </button>
                            </div>
                        </form>

                        <div className="mt-6 overflow-x-auto">
                            {signaturesLoading ? (
                                <p className="py-8 text-center text-sm text-gray-500">Memuat daftar signature…</p>
                            ) : salesSignatures.length === 0 ? (
                                <p className="py-8 text-center text-sm text-gray-500">Belum ada sales atau PIC yang dapat dipilih.</p>
                            ) : (
                                <table className="w-full min-w-[640px] text-left text-sm">
                                    <thead className="border-b border-gray-200 text-xs uppercase text-gray-500">
                                        <tr>
                                            <th className="px-3 py-3 font-semibold">Sales</th>
                                            <th className="px-3 py-3 font-semibold">Posisi / Divisi</th>
                                            <th className="px-3 py-3 font-semibold">Signature</th>
                                            <th className="px-3 py-3 text-right font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {salesSignatures.map((item) => (
                                            <tr key={item.user_id ?? `legacy-${item.id}`}>
                                                <td className="px-3 py-3 font-medium text-gray-900">{item.name}</td>
                                                <td className="px-3 py-3 text-gray-600">
                                                    {[item.position, item.division].filter(Boolean).join(' · ') || '—'}
                                                </td>
                                                <td className="px-3 py-3">
                                                    {item.id !== null ? (
                                                        <img
                                                            src={route('signature-settings.file', item.id)}
                                                            alt={`Signature ${item.name}`}
                                                            className="h-12 w-28 rounded border border-gray-200 bg-white object-contain p-1"
                                                        />
                                                    ) : (
                                                        <span className="text-xs text-amber-700">Belum diunggah</span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-3 text-right">
                                                    {item.id !== null && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setEditingSignature({
                                                                id: item.id!,
                                                                name: item.name,
                                                                division: item.division ?? '',
                                                                position: item.position ?? '',
                                                                file: null,
                                                            })}
                                                            className="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                                        >
                                                            <Pencil size={14} />
                                                            Edit
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </section>
                </div>

                {editingSignature && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                        <section
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="edit-signature-title"
                            className="w-full max-w-lg rounded-xl bg-white shadow-xl"
                        >
                            <form onSubmit={saveSignatureChanges}>
                                <div className="border-b border-gray-100 px-6 py-4">
                                    <h2 id="edit-signature-title" className="font-semibold text-gray-900">Edit Signature Sales</h2>
                                </div>
                                <div className="grid gap-4 p-6">
                                    <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                        Nama
                                        <input
                                            required
                                            maxLength={150}
                                            value={editingSignature.name}
                                            onChange={(event) => setEditingSignature((form) => form
                                                ? { ...form, name: event.target.value }
                                                : form)}
                                            className="h-10 rounded-lg border border-gray-300 px-3 font-normal"
                                        />
                                    </label>
                                    <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                        Divisi
                                        <input
                                            maxLength={50}
                                            value={editingSignature.division}
                                            onChange={(event) => setEditingSignature((form) => form
                                                ? { ...form, division: event.target.value }
                                                : form)}
                                            className="h-10 rounded-lg border border-gray-300 px-3 font-normal"
                                        />
                                    </label>
                                    <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                        Posisi
                                        <input
                                            maxLength={150}
                                            value={editingSignature.position}
                                            onChange={(event) => setEditingSignature((form) => form
                                                ? { ...form, position: event.target.value }
                                                : form)}
                                            className="h-10 rounded-lg border border-gray-300 px-3 font-normal"
                                        />
                                    </label>
                                    <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                        Ganti file TTD (opsional)
                                        <input
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            onChange={(event) => setEditingSignature((form) => form
                                                ? { ...form, file: event.target.files?.[0] ?? null }
                                                : form)}
                                            className="rounded-lg border border-gray-300 p-2 text-sm font-normal"
                                        />
                                        <span className="text-xs font-normal text-gray-500">Kosongkan jika file saat ini tetap digunakan. Maksimal 2 MB.</span>
                                    </label>
                                </div>
                                <div className="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                                    <button
                                        type="button"
                                        disabled={savingEdit}
                                        onClick={() => setEditingSignature(null)}
                                        className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 disabled:opacity-50"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={savingEdit}
                                        className="rounded-lg bg-[#19875f] px-4 py-2 text-sm font-semibold text-white hover:bg-[#146d4c] disabled:opacity-50"
                                    >
                                        {savingEdit ? 'Menyimpan…' : 'Simpan Perubahan'}
                                    </button>
                                </div>
                            </form>
                        </section>
                    </div>
                )}
            </AuthenticatedLayout>
        </>
    );
}
