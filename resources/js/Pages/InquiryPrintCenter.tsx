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
    fetchPrintInquiries,
    fetchPrintTemplates,
    fetchSalesSignatures,
    downloadInquiryExcel,
    downloadInquiryWord,
    downloadInquiryPdf,
    uploadPrintTemplate,
    uploadSalesSignature,
} from './../Services/InquiryPrintApi';

import type {
    InquiryPrintListItem,
    PrintTemplate,
    SalesSignature,
} from './../Services/InquiryPrintApi';

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

    const [day, setDay] =
        useState('');

    const [month, setMonth] =
        useState(
            String(
                now.getMonth() + 1
            )
        );

    const [year, setYear] =
        useState(
            String(
                now.getFullYear()
            )
        );

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

    useEffect(() => {
        if (!selectedInquiryId) {
            setSignatureId('');
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
                String(
                    matchedSignature.id
                )
            );
        } else {
            setSignatureId('');
        }
    }, [
        selectedInquiryId,
        inquiries,
        signatures,
    ]);

    const [
        templateId,
        setTemplateId,
    ] =
        useState('');

    const [
        signatureId,
        setSignatureId,
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


    /*
    |--------------------------------------------------------------------------
    | Load Inquiry
    |--------------------------------------------------------------------------
    */

    useEffect(() => {
        const timer =
            setTimeout(
                async () => {
                    try {
                        setLoading(true);

                        const response =
                            await fetchPrintInquiries({
                                day:
                                    day ||
                                    undefined,

                                month:
                                    month ||
                                    undefined,

                                year:
                                    year ||
                                    undefined,

                                search:
                                    search ||
                                    undefined,
                            });

                        setInquiries(
                            response.data
                        );
                    } catch (error) {
                        console.error(
                            error
                        );
                    } finally {
                        setLoading(false);
                    }
                },
                300
            );

        return () =>
            clearTimeout(timer);

    }, [
        day,
        month,
        year,
        search,
    ]);


    /*
    |--------------------------------------------------------------------------
    | Load Config
    |--------------------------------------------------------------------------
    */

    async function loadPrintConfig() {
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

            const defaultTemplate =
                templateData.find(
                    (
                        item:
                            PrintTemplate
                    ) =>
                        item.is_default
                );

            if (
                defaultTemplate
            ) {
                setTemplateId(
                    String(
                        defaultTemplate.id
                    )
                );
            }
        } catch (error) {
            console.error(
                error
            );
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

            setTemplateModal(
                false
            );

            setTemplateName('');
            setTemplateDivision('');
            setTemplateFile(null);
            setTemplateDefault(false);

            await loadPrintConfig();

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

        quotation_date:
            quotationDate,
    };


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
                                            Export Inquiry Form
                                            dan customer quotation
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
                            day={day}
                            month={month}
                            year={year}
                            search={search}

                            onDayChange={
                                setDay
                            }

                            onMonthChange={
                                setMonth
                            }

                            onYearChange={
                                setYear
                            }

                            onSearchChange={
                                setSearch
                            }
                        />


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
                                        setSelectedInquiryId
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
                                                        signatureId
                                                    }

                                                    onChange={(
                                                        event
                                                    ) =>
                                                        setSignatureId(
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
                                                        No Signature
                                                    </option>

                                                    {signatures.map(
                                                        (
                                                            signature
                                                        ) => (
                                                            <option
                                                                key={
                                                                    signature.id
                                                                }

                                                                value={
                                                                    signature.id
                                                                }
                                                            >
                                                                {
                                                                    signature.name
                                                                }

                                                                {signature.division
                                                                    ?
                                                                    ` - ${signature.division}`
                                                                    :
                                                                    ''}
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
                                                }

                                                onClick={() =>
                                                    downloadInquiryPdf(
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

                                                Download PDF
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        )}
                    </div>
                </div>


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
                                        Template quotation dalam format DOCX.
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
                                        ${'{customer_name}'},
                                        ${'{customer_address}'},
                                        ${'{product_name}'},
                                        ${'{price}'},
                                        dan ${'{signature}'}.
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

            </AuthenticatedLayout>
        </>
    );
}