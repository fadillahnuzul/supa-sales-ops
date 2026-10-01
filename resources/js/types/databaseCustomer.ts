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

    company: string;
    address: string | null;

    segmentationId: number | null;
    segmentation: string;

    level: RiskLevel;
    division: Division;

    pic: string | null;
    phone: string | null;

    sterilization: string | null;
}

export interface Segmentation {
    id: number;
    name: string;
}