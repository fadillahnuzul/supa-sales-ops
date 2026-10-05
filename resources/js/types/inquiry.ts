export interface Inquiry {
    id: number;

    code: string;

    date: string;

    etd: string | null;

    pic: number | null;

    customerId: number | null;

    shippingRate: number | null;

    note: string | null;

    customer?: {
        id: number;

        name: string;

        division?: string | null;

        riskLevel?: string | null;
    } | null;

    picUser?: {
        id: number;

        name: string;
    } | null;

    details: InquiryDetail[];
}

export interface InquiryDetail {
    id: number | string;

    productId: number | null;

    gradeId: number | null;

    qty: number | '';

    /*
    |--------------------------------------------------------------------------
    | Price
    |--------------------------------------------------------------------------
    */

    productStdPrice:
        | number
        | null;

    alternativePrice:
        | number
        | null;

    sourceAP:
        | number
        | null;

    dateAP:
        | string
        | null;

    referencePrice:
        | number
        | null;

    /*
    |--------------------------------------------------------------------------
    | Last Order
    |--------------------------------------------------------------------------
    */

    lastOrderDate:
        | string
        | null;

    lastOrderPrice:
        | number
        | null;

    /*
    |--------------------------------------------------------------------------
    | Last Quotation
    |--------------------------------------------------------------------------
    */

    lastQuotationDate:
        | string
        | null;

    lastQuotationPrice:
        | number
        | null;

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    */

    recommendedPrice:
        | number
        | null;

    approvedPrice:
        | number
        | null;

    approvedDate:
        | string
        | null;

    offer1Price:
        | number
        | null;

    offer2Price:
        | number
        | null;

    offer3Price:
        | number
        | null;

    finalPrice:
        | number
        | null;

    note: string;

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    product?: {
        id: number;

        name: string;

        code: string;
    } | null;

    grade?: {
        id: number;

        name: string;
    } | null;

    competitor?: {
        id: number;

        name: string;
    } | null;

    isNew?: boolean;
}