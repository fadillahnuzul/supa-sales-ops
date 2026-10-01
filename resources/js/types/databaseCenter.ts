export type DatabaseTab =
    | 'customer'
    | 'product'
    | 'competitor';

export type Division =
    | 'Industri'
    | 'SME'
    | 'Low Cost'
    | 'All';

export type RiskLevel =
    | 'Low'
    | 'Medium'
    | 'High';

export interface Customer {
    id: number;
    customerId?: number | string;
    company: string;
    address: string;
    segmentationId?: number | string;
    segmentation?: string;
    level: RiskLevel;
    division: Division;
    pic: string;
    phone: string;
}

export interface Product {
    id: number;
    name: string;
    description: string;
    itemCode: string;
    category: string;
    sterilization: string;
    price: number;
    unit: string;
}

export interface Competitor {
    id: number;
    competitor: string;
    product: string;
    qty: number;
    price: number;
    division: Division;
    notes: string;
    recordedAt: string;
}