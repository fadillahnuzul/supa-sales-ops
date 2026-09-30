export type DatabaseTab =
    | 'customer'
    | 'product'
    | 'competitor';

export type Division =
    | 'INDUSTRY'
    | 'SME'
    | 'LOW COST';

export type RiskLevel =
    | 'Low Risk'
    | 'Medium Risk'
    | 'High Risk';

export interface Customer {
    id: number;
    customerId: string;
    company: string;
    address: string;
    segmentation: string;
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