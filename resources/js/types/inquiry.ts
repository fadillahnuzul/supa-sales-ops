export type RiskLevel =
    | 'Low'
    | 'Medium'
    | 'High';

export interface InquiryDetail {
    id: number | string;

    item: string;
    itemCode: string;
    qty: number | '';

    sourceAP: string;

    lastOrderDate: string | null;
    lastOrderPrice: number | null;

    pricelist: number | null;
    alternativePrice: number | null;
    recommendedPrice: number | null;

    approvedPrice: number | null;
    approvedDate: string | null;

    offer1: number | null;
    offer2: number | null;
    offer3: number | null;

    finalPrice: number | null;

    note: string;

    isNew?: boolean;
}

export interface Inquiry {
    id: number;
    inquiryCode: string;
    inquiryDate: string;
    etd: string;
    picSales: string;
    customer: string;
    segmentation: string;
    level: RiskLevel;
    sterilization: string;
    details: InquiryDetail[];
}