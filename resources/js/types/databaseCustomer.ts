export type RiskLevel =
    | 'Low'
    | 'Medium'
    | 'High';

export type Division =
    | 'Industri'
    | 'SME'
    | 'Low Cost'
    | 'All';

export interface Customer {
    id: number;
    customerId: number;

    code: string | null;
    company: string;
    address: string | null;

    segmentationId: number | null;
    segmentation: string;

    level: RiskLevel;
    division: Division;

    pic: string | null;
    phone: string | null;

    sterilization_id: number | null;
    sterilization: {
        id: number;
        name: string;
    } | null;
}

export interface Segmentation {
    id: number;
    name: string;
}