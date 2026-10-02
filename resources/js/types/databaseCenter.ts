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

export type Sterilization =
    | 'NS'
    | 'S'
    | 'SS';

export interface Customer {
    id: number;
    customerId?: number | string;
    company: string;
    address: string;
    segmentationId?: number | string;
    segmentation?: string;
    level: RiskLevel;
    division: Division;
    sterilization: Sterilization;
    pic: string;
    phone: string;
}

export interface ProductMaterial {
    id: number;

    product_id: number;

    material_id: number;

    material_type:
        | 'material'
        | 'product';

    material?: {
        id: number;
        name: string;
        code?: string | null;
    } | null;
}

export interface Product {
    id: number;

    name: string;

    code: string;

    std_price: number;

    grade_id: number | null;

    grade?: {
        id: number;
        name: string;
    } | null;

    materials: ProductMaterial[];
}
 
export interface MaterialOption {
    id: number;
    name: string;
}

export interface GradeOption {
    id: number;
    name: string;
}

export interface Competitor {
    id: number;
    competitor_id: number;
    competitor: string;
    division: Division;
    competitor_note?: string | null;
    product_id: number;
    product: string;
    price: number;
    date: string;
    notes?: string | null;
}