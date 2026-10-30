import { Head } from '@inertiajs/react';

import {
    CalendarDays,
    Download,
    FileSpreadsheet,
    FileText,
    PenLine,
    Upload,
    X,
} from 'lucide-react';

import {
    useEffect,
    useState,
} from 'react';

import AuthenticatedLayout
    from './../Layouts/AuthenticatedLayout';

import InquiryPrintFilter
    from './../Components/Inquiry/Print/InquiryPrintFilter';

import InquiryPrintTable 
    from './../Components/Inquiry/Print/InquiryPrintTable';

import {
    fetchPrintTemplates,
    fetchSalesSignatures,
    downloadInquiryExcel,
    downloadInquiryWord,
    downloadInquiryPdf,
    uploadPrintTemplate,
    uploadSalesSignature,
    updateSalesSignature,
} from './../Services/InquiryPrintApi';

import type {
    InquiryPrintListItem,
    PrintTemplate,
    SalesSignature,
} from './../Services/InquiryPrintApi';

// Authenticated endpoints; keep the existing API service untouched.
async function deletePrintAsset(kind: 'templates' | 'signatures', id: number) {
    const { default: axios } = await import('axios');
    await axios.delete(`/inquiry-print/assets/${kind}/${id}`);
}
function assetUrl(kind: 'templates' | 'signatures', id: number) {
    return `/inquiry-print/assets/${kind}/${id}/file`;
}
function localDate(date: Date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

interface PicOption {
    id: number;
    name: string;
}

interface Props {
    pics: PicOption[];
}

export default function InquiryPrintCenter({ pics }: Props) {
    const now = new Date();

    /*
    |--------------------------------------------------------------------------
    | Filter
    |--------------------------------------------------------------------------
    */

    const [startDate, setStartDate] = useState(() => localDate(new Date(now.getFullYear(), now.getMonth(), 1)));
    const [endDate, setEndDate] = useState(() => localDate(new Date(now.getFullYear(), now.getMonth() + 1, 0)));
    const [deletingAsset, setDeletingAsset] = useState<string | null>(null);
    const [inquiryError, setInquiryError] = useState('');

    const [search, setSearch] =
        useState('');


    /*
    |--------------------------------------------------------------------------
    | Inquiry
    |--------------------------------------------------------------------------
    */

    const [
        inquiries,
        setInquiries,
    ] =
        useState<
            InquiryPrintListItem[]
        >([]);

    const [
        selectedInquiryId,
        setSelectedInquiryId,
    ] =
        useState<number | null>(
            null
        );

    const [
        loading,
        setLoading,
    ] =
        useState(false);


    /*
    |--------------------------------------------------------------------------
    | Template & Signature
    |--------------------------------------------------------------------------
    */

    const [
        templates,
        setTemplates,
    ] =
        useState<PrintTemplate[]>(
            []
        );

    const [
        signatures,
        setSignatures,
    ] =
        useState<
            SalesSignature[]
        >([]);

    const [
        printConfigError,
        setPrintConfigError,
    ] =
        useState('');

    const [
        signatureId,
        setSignatureId,
    ] =
        useState('');

    const [
        selectedSignatureUserId,
        setSelectedSignatureUserId,
    ] =
        useState('');

    const [
        signatureSelectionLocked,
        setSignatureSelectionLocked,
    ] =
        useState(false);

    useEffect(() => {
        if (signatureSelectionLocked) {
            return;
        }

        if (!selectedInquiryId) {
            setSignatureId('');
            setSelectedSignatureUserId('');
            return;
        }

        const selectedInquiry =
            inquiries.find(
                (inquiry) =>
                    inquiry.id === selectedInquiryId
            );

        if (
            !selectedInquiry
            ||
            !selectedInquiry.pic_id
        ) {
            setSignatureId('');
            setSelectedSignatureUserId('');
            return;
        }

        const matchedSignature =
            signatures.find(
                (signature) =>
                    Number(signature.user_id)
                    ===
                    Number(selectedInquiry.pic_id)
            );

        if (matchedSignature) {
            setSignatureId(
                matchedSignature.id === null
                    ? ''
                    : String(matchedSignature.id)
            );
            setSelectedSignatureUserId(String(matchedSignature.user_id));
        } else {
            setSignatureId('');
            setSelectedSignatureUserId('');
        }
    }, [
        selectedInquiryId,
        inquiries,
        signatures,
        signatureSelectionLocked,
    ]);

    const [
        templateId,
        setTemplateId,
    ] =
        useState('');

    /*
    |--------------------------------------------------------------------------
    | Quotation
    |--------------------------------------------------------------------------
    */

    const [
        quotationNumber,
        setQuotationNumber,
    ] =
        useState('');

    const [
        quotationDate,
        setQuotationDate,
    ] =
        useState(
            new Date()
                .toISOString()
                .slice(0, 10)
        );


    /*
    |--------------------------------------------------------------------------
    | Upload Modal
    |--------------------------------------------------------------------------
    */

    const [
        templateModal,
        setTemplateModal,
    ] =
        useState(false);

    const [
        signatureModal,
        setSignatureModal,
    ] =
        useState(false);

    const [
        assetList,
        setAssetList,
    ] =
        useState<
            'templates' | 'signatures' | null
        >(null);

    const [
        pdfDownloading,
        setPdfDownloading,
    ] =
        useState(false);


    /*
    |--------------------------------------------------------------------------
    | Template Form
    |--------------------------------------------------------------------------
    */

    const [
        templateName,
        setTemplateName,
    ] =
        useState('');

    const [
        templateDivision,
        setTemplateDivision,
    ] =
        useState('');

    const [
        templateFile,
        setTemplateFile,
    ] =
        useState<File | null>(
            null
        );

    const [
        templateDefault,
        setTemplateDefault,
    ] =
        useState(false);


    /*
    |--------------------------------------------------------------------------
    | Signature Form
    |--------------------------------------------------------------------------
    */

    const [
        signatureUserId,
        setSignatureUserId,
    ] =
        useState('');

    const [
        signatureName,
        setSignatureName,
    ] =
        useState('');

    const [
        signatureDivision,
        setSignatureDivision,
    ] =
        useState('');

    const [
        signaturePosition,
        setSignaturePosition,
    ] =
        useState('');

    const [
        signatureFile,
        setSignatureFile,
    ] =
        useState<File | null>(
            null
        );

    const [
        signatureEditForm,
        setSignatureEditForm,
    ] = useState<{
        id: number;
        name: string;
        division: string;
        position: string;
        file: File | null;
    } | null>(null);

    const [
        signatureEditSaving,
        setSignatureEditSaving,
    ] = useState(false);


    /*
    |--------------------------------------------------------------------------
    | Load Inquiry
    |--------------------------------------------------------------------------
    */

    useEffect(() => {
        let cancelled = false;
        if (!startDate || !endDate || startDate > endDate) {
            setInquiryError('Isi tanggal awal dan akhir dengan rentang yang valid.');
            setInquiries([]);
            setLoading(false);
            return;
        }
        setInquiryError('');
        setLoading(true);
        const timer = setTimeout(async () => {
            try {
                const { default: axios } = await import('axios');
                const response = await axios.get('/inquiry-print/date-range', {
                    params: { start_date: startDate, end_date: endDate, search: search || undefined },
                });
                if (!cancelled) setInquiries(response.data.data);
            } catch (error) {
                if (!cancelled) {
                    setInquiries([]);
                    setInquiryError('Gagal memuat inquiry. Silakan coba lagi.');
                }
            } finally {
                if (!cancelled) setLoading(false);
            }
        }, 300);
        return () => { cancelled = true; clearTimeout(timer); };
    }, [startDate, endDate, search]);

    useEffect(() => {
        if (selectedInquiryId && !inquiries.some(item => item.id === selectedInquiryId)) {
            setSelectedInquiryId(null);
            setSignatureSelectionLocked(false);
        }
    }, [inquiries, selectedInquiryId]);

    async function handleDeleteAsset(kind: 'templates' | 'signatures', id: number, name: string) {
        if (deletingAsset || !window.confirm(`Hapus ${kind === 'templates' ? 'template' : 'signature'} "${name}"?`)) return;
        setDeletingAsset(`${kind}:${id}`);
        try {
            await deletePrintAsset(kind, id);
            if (kind === 'templates') {
                const remaining = templates.filter(item => item.id !== id);
                setTemplates(remaining);
                if (templateId === String(id)) setTemplateId(String((remaining.find(item => item.is_default) ?? remaining[0])?.id ?? ''));
            } else {
                setSignatures(items => items.flatMap(item => {
                    if (item.id !== id) {
                        return [item];
                    }

                    return item.user_id === null
                        ? []
                        : [{ ...item, id: null }];
                }));
                if (signatureId === String(id)) {
                    setSignatureId('');
                    setSignatureSelectionLocked(true);
                }
            }
        } catch (error) {
            alert('Gagal menghapus file. Periksa hak akses atau coba lagi.');
        } finally {
            setDeletingAsset(null);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Load Config
    |--------------------------------------------------------------------------
    */

    async function loadPrintConfig(
        preferredTemplateId?: number
    ) {
        try {
            const [
                templateData,
                signatureData,
            ] =
                await Promise.all([
                    fetchPrintTemplates(),
                    fetchSalesSignatures(),
                ]);

            setTemplates(
                templateData
            );

            setSignatures(
                signatureData
            );
            setPrintConfigError('');

            const defaultTemplate =
                templateData.find(
                    (
                        item:
                            PrintTemplate
                    ) =>
                        item.is_default
                );

            const preferredTemplate =
                templateData.find(
                    (item) =>
                        item.id === preferredTemplateId
                );

            const selectedTemplate =
                preferredTemplate
                ??
                (
                    templateId
                        ? undefined
                        : defaultTemplate ?? templateData[0]
                );

            if (selectedTemplate) {
                setTemplateId(
                    String(
                        selectedTemplate.id
                    )
                );
            }
        } catch (error) {
            const { default: axios } = await import('axios');

            console.error('Error loadPrintConfig:', error);

            if (axios.isAxiosError(error)) {
                const status = error.response?.status ?? 'Network Error';
                const url = error.config?.url ?? '';
                const message = error.response?.data?.message
                    || error.message;

                setPrintConfigError(
                    `Gagal memuat (${status}): ${message} — ${url}`
                );
            } else {
                setPrintConfigError(
                    error instanceof Error
                        ? error.message
                        : 'Terjadi kesalahan saat membaca daftar.'
                );
            }
        }
    }

    useEffect(() => {
        loadPrintConfig();
    }, []);


    /*
    |--------------------------------------------------------------------------
    | Upload Template
    |--------------------------------------------------------------------------
    */

    async function handleUploadTemplate() {
        if (
            !templateName.trim()
            ||
            !templateFile
        ) {
            alert(
                'Nama template dan file DOCX wajib diisi.'
            );

            return;
        }

        try {
            const uploadedTemplate =
                await uploadPrintTemplate({
                    name:
                        templateName,

                    division:
                        templateDivision,

                    file:
                        templateFile,

                    is_default:
                        templateDefault,
                });

            setTemplateId(
                String(uploadedTemplate.id)
            );

            setTemplateModal(
                false
            );

            setTemplateName('');
            setTemplateDivision('');
            setTemplateFile(null);
            setTemplateDefault(false);

            await loadPrintConfig(
                uploadedTemplate.id
            );

            alert(
                'Template berhasil diupload.'
            );
        } catch (error) {
            console.error(
                error
            );

            alert(
                'Gagal mengupload template.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Upload Signature
    |--------------------------------------------------------------------------
    */

    async function handleUploadSignature() {
        if (
            !signatureName.trim()
            ||
            !signatureFile
        ) {
            alert(
                'Nama sales dan file signature wajib diisi.'
            );

            return;
        }

        try {
            const uploadedSignature =
                await uploadSalesSignature({
                    user_id:
                        signatureUserId
                            ?
                            Number(
                                signatureUserId
                            )
                            :
                            undefined,

                    name:
                        signatureName,

                    division:
                        signatureDivision,

                    position:
                        signaturePosition,

                    signature:
                        signatureFile,
                });

            setSignatureId(
                String(uploadedSignature.id)
            );
            setSelectedSignatureUserId(
                uploadedSignature.user_id === null
                    ? `legacy:${uploadedSignature.id}`
                    : String(uploadedSignature.user_id)
            );
            setSignatureSelectionLocked(true);

            setSignatureModal(
                false
            );

            setSignatureUserId('');
            setSignatureName('');
            setSignatureDivision('');
            setSignaturePosition('');
            setSignatureFile(null);

            await loadPrintConfig();

            alert(
                'Signature berhasil diupload.'
            );
        } catch (error) {
            console.error(
                error
            );

            alert(
                'Gagal mengupload signature.'
            );
        }
    }

    async function handleUpdateSignature() {
        if (!signatureEditForm) {
            return;
        }

        if (!signatureEditForm.name.trim()) {
            alert('Nama sales wajib diisi.');

            return;
        }

        setSignatureEditSaving(true);

        try {
            const updatedSignature = await updateSalesSignature(
                signatureEditForm.id,
                {
                    name: signatureEditForm.name.trim(),
                    division: signatureEditForm.division,
                    position: signatureEditForm.position,
                    signature: signatureEditForm.file ?? undefined,
                }
            );

            setSignatures((items) => items.map((item) =>
                item.id === updatedSignature.id
                    ? updatedSignature
                    : item
            ));
            setSignatureEditForm(null);
            alert('Signature berhasil diperbarui.');
        } catch (error) {
            console.error(error);
            alert('Gagal memperbarui signature.');
        } finally {
            setSignatureEditSaving(false);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Quotation Payload
    |--------------------------------------------------------------------------
    */

    const quotationPayload = {
        template_id:
            Number(templateId),

        signature_id:
            signatureId
                ?
                Number(
                    signatureId
                )
                :
                null,

        signature_user_id:
            selectedSignatureUserId && !selectedSignatureUserId.startsWith('legacy:')
                ? Number(selectedSignatureUserId)
                : null,

        quotation_date:
            quotationDate,
    };

    function selectInquiry(inquiryId: number | null) {
        setSignatureSelectionLocked(false);
        setSelectedInquiryId(inquiryId);
    }

    async function handleDownloadPdf() {
        if (!selectedInquiryId || !templateId) {
            return;
        }

        setPdfDownloading(true);

        try {
            await downloadInquiryPdf(
                selectedInquiryId,
                quotationPayload
            );
        } catch (error) {
            console.error(error);
            alert('Gagal membuat PDF. Silakan coba lagi.');
        } finally {
            setPdfDownloading(false);
        }
    }


    return (
        <>
            <Head
                title="Print Inquiry"
            />

            <AuthenticatedLayout>
                <div className="
                    min-h-full
                    bg-[#f6f7f8]
                    p-6
                ">
                    <div className="
                        mx-auto
                        max-w-[1700px]
                        space-y-5
                    ">

                        {/* HEADER */}

                        <div className="
                            flex
                            flex-col
                            gap-4
                            lg:flex-row
                            lg:items-center
                            lg:justify-between
                        ">
                            <div>
                                <div className="
                                    flex
                                    items-center
                                    gap-3
                                ">
                                    <div className="
                                        flex
                                        h-11
                                        w-11
                                        items-center
                                        justify-center
                                        rounded-xl
                                        bg-[#19875f]/10
                                        text-[#19875f]
                                    ">
                                        <FileText
                                            size={22}
                                        />
                                    </div>

                                    <div>
                                        <h1 className="
                                            text-2xl
                                            font-semibold
                                            tracking-tight
                                            text-gray-900
                                        ">
                                            Print Inquiry
                                        </h1>

                                        <p className="
                                            mt-1
                                            text-sm
                                            text-gray-500
                                        ">
                                            Export Inquiry Form dan customer quotation
                                        </p>
                                    </div>
                                </div>
                            </div>


                            <div className="
                                flex
                                flex-wrap
                                gap-2
                            ">
                                <button
                                    type="button"
                                    onClick={() =>
                                        setAssetList('signatures')
                                    }
                                    className="
                                        inline-flex
                                        h-10
                                        items-center
                                        gap-2
                                        rounded-lg
                                        border
                                        border-gray-300
                                        bg-white
                                        px-4
                                        text-sm
                                        font-medium
                                        text-gray-700
                                        shadow-sm
                                        transition
                                        hover:bg-gray-50
                                    "
                                >
                                    <PenLine size={16} />
                                    Daftar Signature
                                </button>

                                <button
                                    type="button"
                                    onClick={() =>
                                        setAssetList('templates')
                                    }
                                    className="
                                        inline-flex
                                        h-10
                                        items-center
                                        gap-2
                                        rounded-lg
                                        border
                                        border-gray-300
                                        bg-white
                                        px-4
                                        text-sm
                                        font-medium
                                        text-gray-700
                                        shadow-sm
                                        transition
                                        hover:bg-gray-50
                                    "
                                >
                                    <FileText size={16} />
                                    Daftar Template
                                </button>

                                <button
                                    type="button"
                                    onClick={() =>
                                        setSignatureModal(
                                            true
                                        )
                                    }
                                    className="
                                        inline-flex
                                        h-10
                                        items-center
                                        gap-2
                                        rounded-lg
                                        border
                                        border-gray-300
                                        bg-white
                                        px-4
                                        text-sm
                                        font-medium
                                        text-gray-700
                                        shadow-sm
                                        transition
                                        hover:bg-gray-50
                                    "
                                >
                                    <PenLine
                                        size={16}
                                    />

                                    Upload Signature
                                </button>

                                <button
                                    type="button"
                                    onClick={() =>
                                        setTemplateModal(
                                            true
                                        )
                                    }
                                    className="
                                        inline-flex
                                        h-10
                                        items-center
                                        gap-2
                                        rounded-lg
                                        bg-[#19875f]
                                        px-4
                                        text-sm
                                        font-medium
                                        text-white
                                        shadow-sm
                                        transition
                                        hover:bg-[#146f4e]
                                    "
                                >
                                    <Upload
                                        size={16}
                                    />

                                    Upload Template
                                </button>
                            </div>
                        </div>


                        {/* FILTER */}

                        <InquiryPrintFilter
                            startDate={startDate} endDate={endDate} search={search}
                            onStartDateChange={setStartDate} onEndDateChange={setEndDate}
                            onSearchChange={setSearch}
                        />
                        {inquiryError && <p role="alert" className="text-sm text-red-600">{inquiryError}</p>}


                        {/* TABLE */}

                        <div className="
                            rounded-xl
                            border
                            border-gray-200
                            bg-white
                            shadow-sm
                        ">
                            {loading ? (
                                <div className="
                                    p-10
                                    text-center
                                    text-sm
                                    text-gray-500
                                ">
                                    Loading inquiry...
                                </div>
                            ) : (
                                <InquiryPrintTable
                                    inquiries={
                                        inquiries
                                    }

                                    selectedId={
                                        selectedInquiryId
                                    }

                                    onSelect={
                                        selectInquiry
                                    }
                                />
                            )}
                        </div>


                        {/* NOTHING SELECTED */}

                        {!selectedInquiryId && (
                            <div className="
                                rounded-xl
                                border
                                border-dashed
                                border-gray-300
                                bg-white
                                p-10
                                text-center
                            ">
                                <FileText
                                    size={34}
                                    className="
                                        mx-auto
                                        mb-3
                                        text-gray-300
                                    "
                                />

                                <div className="
                                    text-sm
                                    font-medium
                                    text-gray-700
                                ">
                                    Pilih inquiry terlebih dahulu
                                </div>

                                <p className="
                                    mt-1
                                    text-sm
                                    text-gray-500
                                ">
                                    Setelah dipilih,
                                    pilihan export XLSX,
                                    Word, dan PDF akan tampil.
                                </p>
                            </div>
                        )}


                        {/* PRINT OUTPUT */}

                        {selectedInquiryId && (
                            <div className="
                                grid
                                grid-cols-1
                                gap-5
                                xl:grid-cols-2
                            ">

                                {/* INQUIRY FORM */}

                                <div className="
                                    overflow-hidden
                                    rounded-xl
                                    border
                                    border-gray-200
                                    bg-white
                                    shadow-sm
                                ">
                                    <div className="
                                        flex
                                        items-center
                                        gap-3
                                        border-b
                                        border-gray-100
                                        p-5
                                    ">
                                        <div className="
                                            flex
                                            h-10
                                            w-10
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-[#19875f]/10
                                            text-[#19875f]
                                        ">
                                            <FileSpreadsheet
                                                size={20}
                                            />
                                        </div>

                                        <div>
                                            <h2 className="
                                                font-semibold
                                                text-gray-900
                                            ">
                                                Inquiry Form
                                            </h2>

                                            <p className="
                                                mt-0.5
                                                text-sm
                                                text-gray-500
                                            ">
                                                Internal inquiry document
                                            </p>
                                        </div>
                                    </div>


                                    <div className="p-5">
                                        <div className="
                                            rounded-lg
                                            border
                                            border-gray-200
                                            bg-gray-50
                                            p-4
                                        ">
                                            <div className="
                                                text-xs
                                                font-medium
                                                uppercase
                                                tracking-wide
                                                text-gray-400
                                            ">
                                                Output Format
                                            </div>

                                            <div className="
                                                mt-1
                                                font-medium
                                                text-gray-800
                                            ">
                                                Microsoft Excel (.xlsx)
                                            </div>

                                            <div className="
                                                mt-1
                                                text-sm
                                                text-gray-500
                                            ">
                                                Menggunakan format sheet PRINT_FORM.
                                            </div>
                                        </div>


                                        <button
                                            type="button"
                                            onClick={async () => {
                                                console.log(
                                                    'Download XLSX inquiry:',
                                                    selectedInquiryId
                                                );

                                                if (!selectedInquiryId) {
                                                    alert('Pilih inquiry terlebih dahulu.');
                                                    return;
                                                }

                                                try {
                                                    await downloadInquiryExcel(
                                                        selectedInquiryId
                                                    );

                                                    console.log(
                                                        'Download XLSX selesai'
                                                    );
                                                } catch (error) {
                                                    console.error(
                                                        'Download XLSX error:',
                                                        error
                                                    );

                                                    alert(
                                                        'Gagal download Inquiry Form. Cek console browser.'
                                                    );
                                                }
                                            }}
                                            className="
        mt-5
        inline-flex
        h-11
        w-full
        items-center
        justify-center
        gap-2
        rounded-lg
        bg-[#19875f]
        text-sm
        font-medium
        text-white
        hover:bg-[#146f4e]
    "
                                        >
                                            Download XLSX
                                        </button>
                                    </div>
                                </div>


                                {/* PRINT INQUIRY */}

                                <div className="
                                    overflow-hidden
                                    rounded-xl
                                    border
                                    border-gray-200
                                    bg-white
                                    shadow-sm
                                ">
                                    <div className="
                                        flex
                                        items-center
                                        gap-3
                                        border-b
                                        border-gray-100
                                        p-5
                                    ">
                                        <div className="
                                            flex
                                            h-10
                                            w-10
                                            items-center
                                            justify-center
                                            rounded-lg
                                            bg-[#19875f]/10
                                            text-[#19875f]
                                        ">
                                            <FileText
                                                size={20}
                                            />
                                        </div>

                                        <div>
                                            <h2 className="
                                                font-semibold
                                                text-gray-900
                                            ">
                                                Print Inquiry
                                            </h2>

                                            <p className="
                                                mt-0.5
                                                text-sm
                                                text-gray-500
                                            ">
                                                Customer quotation
                                            </p>
                                        </div>
                                    </div>


                                    <div className="
                                        space-y-4
                                        p-5
                                    ">

                                        {/* TEMPLATE */}

                                        <div>
                                            <label className="
                                                text-sm
                                                font-medium
                                                text-gray-700
                                            ">
                                                Template
                                            </label>

                                            <select
                                                value={
                                                    templateId
                                                }

                                                onChange={(
                                                    event
                                                ) =>
                                                    setTemplateId(
                                                        event
                                                            .target
                                                            .value
                                                    )
                                                }

                                                className="
                                                    mt-1.5
                                                    h-11
                                                    w-full
                                                    rounded-lg
                                                    border
                                                    border-gray-300
                                                    bg-white
                                                    px-3
                                                    text-sm
                                                    outline-none

                                                    focus:border-[#19875f]
                                                    focus:ring-2
                                                    focus:ring-[#19875f]/10
                                                "
                                            >
                                                <option value="">
                                                    Select Template
                                                </option>

                                                {templates.map(
                                                    (
                                                        template
                                                    ) => (
                                                        <option
                                                            key={
                                                                template.id
                                                            }

                                                            value={
                                                                template.id
                                                            }
                                                        >
                                                            {
                                                                template.name
                                                            }

                                                            {template.division
                                                                ?
                                                                ` - ${template.division}`
                                                                :
                                                                ''}
                                                        </option>
                                                    )
                                                )}
                                            </select>

                                            {templates.length === 0 && (
                                                <p className="
                                                    mt-1.5
                                                    text-xs
                                                    text-amber-600
                                                ">
                                                    Belum ada template.
                                                    Upload template DOCX terlebih dahulu.
                                                </p>
                                            )}
                                        </div>

                                        <div className="
                                            grid
                                            grid-cols-1
                                            gap-4
                                            md:grid-cols-2
                                        ">
                                            {/* SIGNATURE */}

                                            <div>
                                                <label className="
                                                text-sm
                                                font-medium
                                                text-gray-700
                                            ">
                                                    Sales / Signature
                                                </label>

                                                <select
                                                    value={
                                                        selectedSignatureUserId
                                                    }

                                                    onChange={(
                                                        event
                                                    ) => {
                                                        setSignatureSelectionLocked(true);
                                                        const value = event.target.value;

                                                        if (!value) {
                                                            setSignatureId('');
                                                            setSelectedSignatureUserId('');

                                                            return;
                                                        }

                                                        if (value.startsWith('legacy:')) {
                                                            setSignatureId(value.slice(7));
                                                            setSelectedSignatureUserId(value);

                                                            return;
                                                        }

                                                        const selectedSignature = signatures.find(
                                                            (signature) =>
                                                                String(signature.user_id) === value
                                                        );
                                                        setSignatureId(
                                                            selectedSignature?.id === null
                                                                || selectedSignature?.id === undefined
                                                                ? ''
                                                                : String(selectedSignature.id)
                                                        );
                                                        setSelectedSignatureUserId(value);
                                                    }}

                                                    className="
                                                    mt-1.5
                                                    h-11
                                                    w-full
                                                    rounded-lg
                                                    border
                                                    border-gray-300
                                                    bg-white
                                                    px-3
                                                    text-sm
                                                    outline-none

                                                    focus:border-[#19875f]
                                                    focus:ring-2
                                                    focus:ring-[#19875f]/10
                                                "
                                                >
                                                    <option value="">
                                                        No Signature
                                                    </option>

                                                    {signatures.map(
                                                        (
                                                            signature
                                                        ) => (
                                                            <option
                                                                key={
                                                                    signature.user_id === null
                                                                        ? `legacy-${signature.id}`
                                                                        : `employee-${signature.user_id}`
                                                                }

                                                                value={
                                                                    signature.user_id === null
                                                                        ? `legacy:${signature.id}`
                                                                        : signature.user_id
                                                                }
                                                            >
                                                                {
                                                                    signature.name
                                                                }

                                                                {signature.division
                                                                    ?
                                                                    ` - ${signature.division}`
                                                                    : signature.position
                                                                        ? ` - ${signature.position}`
                                                                        : ''}
                                                            </option>
                                                        )
                                                    )}
                                                </select>
                                            </div>






                                            {/* DATE */}

                                            <div>
                                                <label className="
                                                    text-sm
                                                    font-medium
                                                    text-gray-700
                                                ">
                                                    Quotation Date
                                                </label>

                                                <div className="
                                                    relative
                                                    mt-1.5
                                                ">
                                                    <CalendarDays
                                                        size={16}
                                                        className="
                                                            absolute
                                                            left-3
                                                            top-1/2
                                                            -translate-y-1/2
                                                            text-gray-400
                                                        "
                                                    />

                                                    <input
                                                        type="date"

                                                        value={
                                                            quotationDate
                                                        }

                                                        onChange={(
                                                            event
                                                        ) =>
                                                            setQuotationDate(
                                                                event
                                                                    .target
                                                                    .value
                                                            )
                                                        }

                                                        className="
                                                            h-11
                                                            w-full
                                                            rounded-lg
                                                            border
                                                            border-gray-300
                                                            pl-10
                                                            pr-3
                                                            text-sm
                                                            outline-none

                                                            focus:border-[#19875f]
                                                            focus:ring-2
                                                            focus:ring-[#19875f]/10
                                                        "
                                                    />
                                                </div>
                                            </div>
                                        </div>


                                        {/* BUTTON */}

                                        <div className="
                                            grid
                                            grid-cols-1
                                            gap-3
                                            pt-2
                                            sm:grid-cols-2
                                        ">
                                            <button
                                                type="button"

                                                disabled={
                                                    !templateId
                                                }

                                                onClick={() =>
                                                    downloadInquiryWord(
                                                        selectedInquiryId,
                                                        quotationPayload
                                                    )
                                                }

                                                className="
                                                    inline-flex
                                                    h-11
                                                    items-center
                                                    justify-center
                                                    gap-2
                                                    rounded-lg
                                                    border
                                                    border-gray-300
                                                    bg-white
                                                    text-sm
                                                    font-medium
                                                    text-gray-700
                                                    transition

                                                    hover:bg-gray-50
                                                    disabled:cursor-not-allowed
                                                    disabled:opacity-40
                                                "
                                            >
                                                <Download
                                                    size={17}
                                                />

                                                Download Word
                                            </button>

                                            <button
                                                type="button"

                                                disabled={
                                                    !templateId
                                                    || pdfDownloading
                                                }

                                                onClick={handleDownloadPdf}

                                                className="
                                                    inline-flex
                                                    h-11
                                                    items-center
                                                    justify-center
                                                    gap-2
                                                    rounded-lg
                                                    bg-[#19875f]
                                                    text-sm
                                                    font-medium
                                                    text-white
                                                    transition

                                                    hover:bg-[#146f4e]
                                                    disabled:cursor-not-allowed
                                                    disabled:opacity-40
                                                "
                                            >
                                                <Download
                                                    size={17}
                                                />

                                                {pdfDownloading
                                                    ? 'Memproses PDF...'
                                                    : 'Download PDF'}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        )}
                    </div>
                </div>

                {assetList !== null && (
                    <div
                        className="
                            fixed
                            inset-0
                            z-50
                            flex
                            items-center
                            justify-center
                            bg-black/40
                            p-4
                        "
                    >
                        <section
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="asset-list-title"
                            className="
                                max-h-[85vh]
                                w-full
                                max-w-2xl
                                overflow-hidden
                                rounded-xl
                                bg-white
                                shadow-xl
                            "
                        >
                            <div className="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-gray-100
                                px-6
                                py-4
                            ">
                                <div>
                                    <h2
                                        id="asset-list-title"
                                        className="font-semibold text-gray-900"
                                    >
                                        {assetList === 'templates'
                                            ? 'Daftar Template'
                                            : 'Daftar Signature'}
                                    </h2>
                                    <p className="mt-0.5 text-sm text-gray-500">
                                        {assetList === 'templates'
                                            ? 'Pilih template aktif yang akan digunakan.'
                                            : 'Pilih signature aktif yang akan digunakan.'}
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onClick={() => setAssetList(null)}
                                    aria-label="Tutup daftar"
                                    className="
                                        rounded-lg
                                        p-2
                                        text-gray-400
                                        hover:bg-gray-100
                                    "
                                >
                                    <X size={18} />
                                </button>
                            </div>

                            <div className="max-h-[65vh] overflow-y-auto p-4">
                                {printConfigError ? (
                                    <p
                                        role="alert"
                                        className="p-6 text-center text-sm text-red-600"
                                    >
                                        {printConfigError}
                                    </p>
                                ) : assetList === 'templates' ? (
                                    templates.length === 0 ? (
                                        <p className="p-6 text-center text-sm text-gray-500">
                                            Belum ada template aktif.
                                        </p>
                                    ) : (
                                        <div className="divide-y divide-gray-100">
                                            {templates.map((template) => (
                                                <div
                                                    key={template.id}
                                                    className="flex flex-wrap items-center justify-between gap-4 py-3"
                                                >
                                                    <div className="min-w-0">
                                                        <p className="truncate text-sm font-medium text-gray-900">
                                                            {template.name}
                                                        </p>
                                                        <p className="mt-1 text-xs text-gray-500">
                                                            {template.code}
                                                            {template.division
                                                                ? ` · ${template.division}`
                                                                : ''}
                                                        </p>
                                                    </div>
                                                    <div className="flex shrink-0 items-center gap-2">
                                                        <a href={assetUrl('templates', template.id)} target="_blank" rel="noopener noreferrer" className="rounded-lg border px-3 py-1.5 text-xs text-green-700">Lihat File</a>
                                                        <button type="button" disabled={deletingAsset !== null} onClick={() => handleDeleteAsset('templates', template.id, template.name)} className="rounded-lg border border-red-200 px-3 py-1.5 text-xs text-red-600 disabled:opacity-50">{deletingAsset === `templates:${template.id}` ? 'Menghapus…' : 'Hapus'}</button>
                                                        {template.is_default && (
                                                            <span className="rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                                                Default
                                                            </span>
                                                        )}
                                                        <button
                                                            type="button"
                                                            onClick={() => {
                                                                setTemplateId(String(template.id));
                                                                setAssetList(null);
                                                            }}
                                                            className="
                                                                rounded-lg
                                                                border
                                                                border-gray-300
                                                                px-3
                                                                py-1.5
                                                                text-xs
                                                                font-medium
                                                                text-gray-700
                                                                hover:bg-gray-50
                                                            "
                                                        >
                                                            {String(template.id) === templateId
                                                                ? 'Dipilih'
                                                                : 'Pakai'}
                                                        </button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )
                                ) : signatures.every((signature) => signature.id === null) ? (
                                    <p className="p-6 text-center text-sm text-gray-500">
                                        Belum ada signature aktif.
                                    </p>
                                ) : (
                                    <div className="divide-y divide-gray-100">
                                        {signatures.map((signature) => {
                                            if (signature.id === null) {
                                                return null;
                                            }

                                            return (
                                                <div
                                                    key={signature.id}
                                                    className="flex flex-wrap items-center justify-between gap-4 py-3"
                                                >
                                                    <a href={assetUrl('signatures', signature.id)} target="_blank" rel="noopener noreferrer" aria-label={`Lihat signature ${signature.name}`} className="shrink-0">
                                                        <img src={assetUrl('signatures', signature.id)} alt={`Signature ${signature.name}`} loading="lazy" className="h-20 w-32 rounded-lg border bg-white object-contain p-2" />
                                                    </a>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate text-sm font-medium text-gray-900">
                                                            {signature.name}
                                                        </p>
                                                        <p className="mt-1 text-xs text-gray-500">
                                                            {[signature.position, signature.division]
                                                                .filter(Boolean)
                                                                .join(' · ') || 'Jabatan/divisi belum diisi'}
                                                        </p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        disabled={deletingAsset !== null || signatureEditSaving}
                                                        onClick={() => setSignatureEditForm({
                                                            id: signature.id!,
                                                            name: signature.name,
                                                            division: signature.division ?? '',
                                                            position: signature.position ?? '',
                                                            file: null,
                                                        })}
                                                        className="flex shrink-0 items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                                                    >
                                                        <PenLine size={14} />
                                                        Edit
                                                    </button>
                                                    <button type="button" disabled={deletingAsset !== null} onClick={() => handleDeleteAsset('signatures', signature.id, signature.name)} className="rounded-lg border border-red-200 px-3 py-1.5 text-xs text-red-600 disabled:opacity-50">{deletingAsset === `signatures:${signature.id}` ? 'Menghapus…' : 'Hapus'}</button>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            setSignatureSelectionLocked(true);
                                                            setSignatureId(String(signature.id));
                                                            setSelectedSignatureUserId(
                                                                signature.user_id === null
                                                                    ? `legacy:${signature.id}`
                                                                    : String(signature.user_id)
                                                            );
                                                            setAssetList(null);
                                                        }}
                                                        className="
                                                            shrink-0
                                                            rounded-lg
                                                            border
                                                            border-gray-300
                                                            px-3
                                                            py-1.5
                                                            text-xs
                                                            font-medium
                                                            text-gray-700
                                                            hover:bg-gray-50
                                                        "
                                                    >
                                                        {String(signature.id) === signatureId
                                                            ? 'Dipilih'
                                                            : 'Pakai'}
                                                    </button>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        </section>
                    </div>
                )}

                {/* TEMPLATE MODAL */}

                {templateModal && (
                    <div className="
                        fixed
                        inset-0
                        z-50
                        flex
                        items-center
                        justify-center
                        bg-black/40
                        p-4
                    ">
                        <div className="
                            w-full
                            max-w-lg
                            rounded-xl
                            bg-white
                            shadow-xl
                        ">
                            <div className="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-gray-100
                                px-6
                                py-4
                            ">
                                <div>
                                    <h2 className="
                                        font-semibold
                                        text-gray-900
                                    ">
                                        Upload Print Template
                                    </h2>

                                    <p className="
                                        mt-0.5
                                        text-sm
                                        text-gray-500
                                    ">
                                        Pastikan DOCX memiliki placeholder yang ingin diganti, lalu upload versi terbaru.
                                    </p>
                                </div>

                                <button
                                    onClick={() =>
                                        setTemplateModal(
                                            false
                                        )
                                    }
                                    className="
                                        rounded-lg
                                        p-2
                                        text-gray-400
                                        hover:bg-gray-100
                                    "
                                >
                                    <X size={18} />
                                </button>
                            </div>


                            <div className="
                                space-y-4
                                p-6
                            ">
                                <div>
                                    <label className="
                                        text-sm
                                        font-medium
                                        text-gray-700
                                    ">
                                        Template Name
                                    </label>

                                    <input
                                        value={
                                            templateName
                                        }

                                        onChange={(
                                            event
                                        ) =>
                                            setTemplateName(
                                                event
                                                    .target
                                                    .value
                                            )
                                        }

                                        placeholder="SUPA Domestic Quotation"

                                        className="
                                            mt-1.5
                                            h-11
                                            w-full
                                            rounded-lg
                                            border
                                            border-gray-300
                                            px-3
                                            text-sm
                                            outline-none

                                            focus:border-[#19875f]
                                            focus:ring-2
                                            focus:ring-[#19875f]/10
                                        "
                                    />
                                </div>


                                <div>
                                    <label className="
                                        text-sm
                                        font-medium
                                        text-gray-700
                                    ">
                                        Division
                                    </label>

                                    <select
                                        value={
                                            templateDivision
                                        }

                                        onChange={(
                                            event
                                        ) =>
                                            setTemplateDivision(
                                                event
                                                    .target
                                                    .value
                                            )
                                        }

                                        className="
                                            mt-1.5
                                            h-11
                                            w-full
                                            rounded-lg
                                            border
                                            border-gray-300
                                            px-3
                                            text-sm
                                            outline-none

                                            focus:border-[#19875f]
                                        "
                                    >
                                        <option value="">
                                            All Division
                                        </option>

                                        <option value="DTT">
                                            DTT
                                        </option>

                                        <option value="GTT">
                                            GTT
                                        </option>
                                    </select>
                                </div>


                                <div>
                                    <label className="
                                        text-sm
                                        font-medium
                                        text-gray-700
                                    ">
                                        DOCX Template
                                    </label>

                                    <input
                                        type="file"
                                        accept=".docx"

                                        onChange={(
                                            event
                                        ) =>
                                            setTemplateFile(
                                                event
                                                    .target
                                                    .files?.[0]
                                                ??
                                                null
                                            )
                                        }

                                        className="
                                            mt-1.5
                                            block
                                            w-full
                                            rounded-lg
                                            border
                                            border-gray-300
                                            p-2.5
                                            text-sm
                                        "
                                    />

                                    <p className="
                                        mt-1.5
                                        text-xs
                                        text-gray-500
                                    ">
                                        Gunakan placeholder seperti
                                        ${'{inquiry_code}'},
                                        ${'{customer_name}'},
                                        ${'{customer_address}'},
                                        ${'{product_name}'},
                                        ${'{price}'},
                                        dan ${'{signature}'}.
                                        Perubahan kode tidak mengubah file DOCX yang sudah diupload.
                                    </p>
                                </div>


                                <label className="
                                    flex
                                    items-center
                                    gap-2
                                    text-sm
                                    text-gray-700
                                ">
                                    <input
                                        type="checkbox"

                                        checked={
                                            templateDefault
                                        }

                                        onChange={(
                                            event
                                        ) =>
                                            setTemplateDefault(
                                                event
                                                    .target
                                                    .checked
                                            )
                                        }
                                    />

                                    Jadikan template default
                                </label>
                            </div>


                            <div className="
                                flex
                                justify-end
                                gap-2
                                border-t
                                border-gray-100
                                px-6
                                py-4
                            ">
                                <button
                                    type="button"

                                    onClick={() =>
                                        setTemplateModal(
                                            false
                                        )
                                    }

                                    className="
                                        h-10
                                        rounded-lg
                                        border
                                        border-gray-300
                                        px-4
                                        text-sm
                                        font-medium
                                        text-gray-700
                                    "
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"

                                    onClick={
                                        handleUploadTemplate
                                    }

                                    className="
                                        h-10
                                        rounded-lg
                                        bg-[#19875f]
                                        px-4
                                        text-sm
                                        font-medium
                                        text-white
                                        hover:bg-[#146f4e]
                                    "
                                >
                                    Upload Template
                                </button>
                            </div>
                        </div>
                    </div>
                )}


                {/* SIGNATURE MODAL */}

                {signatureModal && (
                    <div className="
                        fixed
                        inset-0
                        z-50
                        flex
                        items-center
                        justify-center
                        bg-black/40
                        p-4
                    ">
                        <div className="
                            w-full
                            max-w-lg
                            rounded-xl
                            bg-white
                            shadow-xl
                        ">
                            <div className="
                                flex
                                items-center
                                justify-between
                                border-b
                                border-gray-100
                                px-6
                                py-4
                            ">
                                <div>
                                    <h2 className="
                                        font-semibold
                                        text-gray-900
                                    ">
                                        Upload Sales Signature
                                    </h2>

                                    <p className="
                                        mt-0.5
                                        text-sm
                                        text-gray-500
                                    ">
                                        Upload TTD + stempel masing-masing sales.
                                    </p>
                                </div>

                                <button
                                    onClick={() =>
                                        setSignatureModal(
                                            false
                                        )
                                    }

                                    className="
                                        rounded-lg
                                        p-2
                                        text-gray-400
                                        hover:bg-gray-100
                                    "
                                >
                                    <X size={18} />
                                </button>
                            </div>


                            <div className="
                                p-3
                            ">
                                <div>
                                    <label className="
                                        text-sm
                                        font-medium
                                        text-gray-700
                                    ">
                                        Nama Sales
                                    </label>

                                    <select
                                        value={signatureUserId}
                                        onChange={(event) => {
                                            const value =
                                                event.target.value;

                                            setSignatureUserId(
                                                value
                                            );

                                            const selectedPic =
                                                pics.find(
                                                    (pic) =>
                                                        pic.id ===
                                                        Number(value)
                                                );

                                            setSignatureName(
                                                selectedPic?.name
                                                ?? ''
                                            );
                                        }}
                                        className="
        mt-1.5
        h-11
        w-full
        rounded-lg
        border
        border-gray-300
        bg-white
        px-3
        text-sm
        outline-none
        focus:border-[#19875f]
        focus:ring-2
        focus:ring-[#19875f]/10
    "
                                    >
                                        <option value="">
                                            Pilih PIC Sales
                                        </option>

                                        {pics.map((pic) => (
                                            <option
                                                key={pic.id}
                                                value={pic.id}
                                            >
                                                {pic.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>


                                <div className="
                                    grid
                                    grid-cols-2
                                    gap-4
                                ">
                                    <div>
                                        <label className="
                                            text-sm
                                            font-medium
                                            text-gray-700
                                        ">
                                            Division
                                        </label>

                                        <select
                                            value={
                                                signatureDivision
                                            }

                                            onChange={(
                                                event
                                            ) =>
                                                setSignatureDivision(
                                                    event
                                                        .target
                                                        .value
                                                )
                                            }

                                            className="
                                                mt-1.5
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-gray-300
                                                px-3
                                                text-sm
                                            "
                                        >
                                            <option value="">
                                                Select
                                            </option>

                                            <option value="DTT">
                                                DTT
                                            </option>

                                            <option value="GTT">
                                                GTT
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="
                                            text-sm
                                            font-medium
                                            text-gray-700
                                        ">
                                            Position
                                        </label>

                                        <input
                                            value={
                                                signaturePosition
                                            }

                                            onChange={(
                                                event
                                            ) =>
                                                setSignaturePosition(
                                                    event
                                                        .target
                                                        .value
                                                )
                                            }

                                            placeholder="Snr. Specialist"

                                            className="
                                                mt-1.5
                                                h-11
                                                w-full
                                                rounded-lg
                                                border
                                                border-gray-300
                                                px-3
                                                text-sm
                                            "
                                        />
                                    </div>
                                </div>


                                <div>
                                    <label className="
                                        text-sm
                                        font-medium
                                        text-gray-700
                                    ">
                                        TTD + Stamp
                                    </label>

                                    <input
                                        type="file"

                                        accept="
                                            image/png,
                                            image/jpeg,
                                            image/webp
                                        "

                                        onChange={(
                                            event
                                        ) =>
                                            setSignatureFile(
                                                event
                                                    .target
                                                    .files?.[0]
                                                ??
                                                null
                                            )
                                        }

                                        className="
                                            mt-1.5
                                            block
                                            w-full
                                            rounded-lg
                                            border
                                            border-gray-300
                                            p-2.5
                                            text-sm
                                        "
                                    />

                                    <p className="
                                        mt-1.5
                                        text-xs
                                        text-gray-500
                                    ">
                                        Disarankan PNG transparan.
                                        Maksimal 2 MB.
                                    </p>
                                </div>
                            </div>


                            <div className="
                                flex
                                justify-end
                                gap-2
                                border-t
                                border-gray-100
                                px-6
                                py-4
                            ">
                                <button
                                    type="button"

                                    onClick={() =>
                                        setSignatureModal(
                                            false
                                        )
                                    }

                                    className="
                                        h-10
                                        rounded-lg
                                        border
                                        border-gray-300
                                        px-4
                                        text-sm
                                        font-medium
                                        text-gray-700
                                    "
                                >
                                    Cancel
                                </button>

                                <button
                                    type="button"

                                    onClick={
                                        handleUploadSignature
                                    }

                                    className="
                                        h-10
                                        rounded-lg
                                        bg-[#19875f]
                                        px-4
                                        text-sm
                                        font-medium
                                        text-white
                                        hover:bg-[#146f4e]
                                    "
                                >
                                    Upload Signature
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {signatureEditForm && (
                    <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4">
                        <section
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="edit-signature-title"
                            className="w-full max-w-lg rounded-xl bg-white shadow-xl"
                        >
                            <div className="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                                <div>
                                    <h2 id="edit-signature-title" className="font-semibold text-gray-900">
                                        Edit Signature
                                    </h2>
                                    <p className="mt-0.5 text-sm text-gray-500">
                                        Perbarui data sales atau ganti file tanda tangannya.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    aria-label="Tutup edit signature"
                                    onClick={() => setSignatureEditForm(null)}
                                    className="rounded-lg p-2 text-gray-400 hover:bg-gray-100"
                                >
                                    <X size={18} />
                                </button>
                            </div>

                            <div className="grid gap-4 p-6">
                                <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                    Nama Sales
                                    <input
                                        required
                                        maxLength={150}
                                        value={signatureEditForm.name}
                                        onChange={(event) => setSignatureEditForm((form) => form
                                            ? { ...form, name: event.target.value }
                                            : form)}
                                        className="h-11 rounded-lg border border-gray-300 px-3 font-normal outline-none focus:border-[#19875f] focus:ring-2 focus:ring-[#19875f]/10"
                                    />
                                </label>

                                <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                    Division
                                    <input
                                        maxLength={50}
                                        value={signatureEditForm.division}
                                        onChange={(event) => setSignatureEditForm((form) => form
                                            ? { ...form, division: event.target.value }
                                            : form)}
                                        className="h-11 rounded-lg border border-gray-300 px-3 font-normal outline-none focus:border-[#19875f] focus:ring-2 focus:ring-[#19875f]/10"
                                    />
                                </label>

                                <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                    Posisi
                                    <input
                                        maxLength={150}
                                        value={signatureEditForm.position}
                                        onChange={(event) => setSignatureEditForm((form) => form
                                            ? { ...form, position: event.target.value }
                                            : form)}
                                        className="h-11 rounded-lg border border-gray-300 px-3 font-normal outline-none focus:border-[#19875f] focus:ring-2 focus:ring-[#19875f]/10"
                                    />
                                </label>

                                <label className="grid gap-1.5 text-sm font-medium text-gray-700">
                                    Ganti file TTD (opsional)
                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        onChange={(event) => setSignatureEditForm((form) => form
                                            ? { ...form, file: event.target.files?.[0] ?? null }
                                            : form)}
                                        className="block w-full rounded-lg border border-gray-300 p-2.5 text-sm font-normal"
                                    />
                                    <span className="text-xs font-normal text-gray-500">
                                        PNG, JPG, atau WEBP; maksimal 2 MB. Kosongkan jika tidak ingin mengganti file.
                                    </span>
                                </label>
                            </div>

                            <div className="flex justify-end gap-2 border-t border-gray-100 px-6 py-4">
                                <button
                                    type="button"
                                    disabled={signatureEditSaving}
                                    onClick={() => setSignatureEditForm(null)}
                                    className="h-10 rounded-lg border border-gray-300 px-4 text-sm font-medium text-gray-700 disabled:opacity-50"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    disabled={signatureEditSaving}
                                    onClick={handleUpdateSignature}
                                    className="h-10 rounded-lg bg-[#19875f] px-4 text-sm font-medium text-white hover:bg-[#146f4e] disabled:opacity-50"
                                >
                                    {signatureEditSaving ? 'Menyimpan…' : 'Simpan Perubahan'}
                                </button>
                            </div>
                        </section>
                    </div>
                )}

            </AuthenticatedLayout>
        </>
    );
}