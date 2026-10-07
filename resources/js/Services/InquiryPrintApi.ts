import axios from "axios";

const api = axios.create({
    baseURL: "/api",
});

export type InquiryPrintListItem = {
    id: number;
    code: string;
    date: string;

    pic_id: number | null;
    pic_name: string | null;
    pic_first_name: string | null;

    customer_name: string | null;
    total_items: number;
};

export type PrintTemplate = {
    id: number;
    name: string;
    code: string;
    division?: string;
    is_default: boolean;
};

export type SalesSignature = {
    id: number;
    user_id?: number;
    name: string;
    division?: string;
    position?: string;
};

export interface UploadTemplatePayload {
    name: string;
    division?: string;
    file: File;
    is_default?: boolean;
}

export interface UploadSignaturePayload {
    user_id?: number;
    name: string;
    division?: string;
    position?: string;
    signature: File;
}


/*
|--------------------------------------------------------------------------
| Upload Template
|--------------------------------------------------------------------------
*/

export async function uploadPrintTemplate(
    payload: UploadTemplatePayload
) {
    const formData =
        new FormData();

    formData.append(
        'name',
        payload.name
    );

    if (payload.division) {
        formData.append(
            'division',
            payload.division
        );
    }

    formData.append(
        'file',
        payload.file
    );

    formData.append(
        'is_default',
        payload.is_default
            ? '1'
            : '0'
    );


    const response =
        await api.post(
            '/inquiries/print/templates',
            formData,
            {
                headers: {
                    'Content-Type':
                        'multipart/form-data',
                },
            }
        );

    return response.data;
}


/*
|--------------------------------------------------------------------------
| Upload Signature
|--------------------------------------------------------------------------
*/

export async function uploadSalesSignature(
    payload: UploadSignaturePayload
) {
    const formData =
        new FormData();

    if (payload.user_id) {
        formData.append(
            'user_id',
            String(
                payload.user_id
            )
        );
    }

    formData.append(
        'name',
        payload.name
    );

    if (payload.division) {
        formData.append(
            'division',
            payload.division
        );
    }

    if (payload.position) {
        formData.append(
            'position',
            payload.position
        );
    }

    formData.append(
        'signature',
        payload.signature
    );


    const response =
        await api.post(
            '/inquiries/print/signatures',
            formData,
            {
                headers: {
                    'Content-Type':
                        'multipart/form-data',
                },
            }
        );

    return response.data;
}

export async function fetchPrintInquiries(
    params: {
        day?: string;
        month?: string;
        year?: string;
        search?: string;
        page?: number;
    }
) {
    const response =
        await api.get(
            "/inquiries/print",
            { params }
        );

    return response.data;
}

export async function fetchPrintInquiry(
    id: number
) {
    const response =
        await api.get(
            `/inquiries/print/${id}`
        );

    return response.data;
}

export async function fetchPrintTemplates() {
    const response =
        await api.get(
            '/inquiries/print/templates'
        );

    return response.data;
}

export async function fetchSalesSignatures() {
    const response =
        await api.get(
            '/inquiries/print/signatures'
        );

    return response.data;
}

export async function downloadInquiryExcel(
    id: number
) {
    const response =
        await api.get(
            `/inquiries/print/${id}/excel`,
            {
                responseType: "blob",
            }
        );

    downloadBlob(
        response.data,
        `INQUIRY_FORM_${id}.xlsx`
    );
}

export async function downloadInquiryWord(
    id: number,
    payload: any
) {
    const response =
        await api.post(
            `/inquiries/print/${id}/word`,
            payload,
            {
                responseType: "blob",
            }
        );

    downloadBlob(
        response.data,
        `QUOTATION_${id}.docx`
    );
}

export async function downloadInquiryPdf(
    id: number,
    payload: any
) {
    const response =
        await api.post(
            `/inquiries/print/${id}/pdf`,
            payload,
            {
                responseType: "blob",
            }
        );

    downloadBlob(
        response.data,
        `QUOTATION_${id}.pdf`
    );
}


function downloadBlob(
    data: Blob,
    filename: string
) {
    const url =
        window.URL.createObjectURL(data);

    const anchor =
        document.createElement("a");

    anchor.href = url;
    anchor.download = filename;

    document.body.appendChild(anchor);

    anchor.click();

    anchor.remove();

    window.URL.revokeObjectURL(url);
}