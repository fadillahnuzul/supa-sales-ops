import type {
    Customer,
    Product,
    Competitor,
} from '../types/databaseCenter';

export const customers: Customer[] = [
    {
        id: 1,
        customerId: 'cust-1',
        company: '3Indy',
        address:
            'Park Royal Regency C 3/15 Buduran, Sidoarjo, Jawa Timur',
        segmentation: 'General Food Processing',
        level: 'Medium Risk',
        division: 'LOW COST',
        pic: 'Pak Hendra',
        phone: '0812-3344-5566',
    },
    {
        id: 2,
        customerId: 'cust-7',
        company: 'CV Berkah Snack Sejahtera',
        address:
            'Jl. Raya Sepanjang KM 14, Taman, Sidoarjo',
        segmentation: 'Snack Industry',
        level: 'High Risk',
        division: 'LOW COST',
        pic: 'Slamet Riyadi',
        phone: '0813-9090-8811',
    },
    {
        id: 3,
        customerId: 'cust-5',
        company: 'CV Rasa Nusantara Makmur',
        address:
            'Kawasan Industri Rungkut Megah Raya Blok D-7, Surabaya',
        segmentation: 'Wet Seasoning',
        level: 'Medium Risk',
        division: 'SME',
        pic: 'Cahyo Utomo',
        phone: '031-870-9944',
    },
    {
        id: 4,
        customerId: 'cust-10',
        company: 'Hotel Majapahit Surabaya MGallery',
        address:
            'Jl. Tunjungan No. 65, Surabaya, Jawa Timur',
        segmentation: 'HORECA',
        level: 'Low Risk',
        division: 'SME',
        pic: 'Executive Chef Herman',
        phone: '031-545-4333',
    },
];

export const products: Product[] = [
    {
        id: 1,
        name: 'Allspice Crushed',
        description:
            'Crushed Jamaican allspice, clean aroma, steam sterilized.',
        itemCode: 'SSN-ASP-CR01',
        category: 'Spices & Herbs',
        sterilization: 'S (Steam)',
        price: 140000,
        unit: 'KG',
    },
    {
        id: 2,
        name: 'Allspice Ground',
        description:
            'Fine ground allspice powder mesh 60-80, food grade.',
        itemCode: 'SSN-ASP-GR02',
        category: 'Spices & Herbs',
        sterilization: 'S (Steam)',
        price: 145000,
        unit: 'KG',
    },
    {
        id: 3,
        name: 'Allspice Seeds',
        description:
            'Whole selected allspice berries, moisture < 12%.',
        itemCode: 'SSN-ASP-SD03',
        category: 'Whole Spices',
        sterilization: 'S (Steam)',
        price: 135000,
        unit: 'KG',
    },
    {
        id: 4,
        name: 'Almond Ground',
        description:
            'Premium ground California almond meal for bakery & beverages.',
        itemCode: 'SSN-ALM-GR01',
        category: 'Nuts & Seeds',
        sterilization: 'S (Steam)',
        price: 190000,
        unit: 'KG',
    },
    {
        id: 5,
        name: 'Black Pepper Ground 550GL',
        description:
            'Lampung black pepper ground 550GL mesh 40-60.',
        itemCode: 'SSN-BP-GR01',
        category: 'Pepper',
        sterilization: 'S (Steam)',
        price: 115000,
        unit: 'KG',
    },
];

export const competitors: Competitor[] = [
    {
        id: 1,
        competitor: 'Cahaya Pelita',
        product: 'Cayenne Powder',
        qty: 50,
        price: 125000,
        division: 'INDUSTRY',
        notes:
            'Kompetitor utama area Cikarang dan Jawa Barat',
        recordedAt: '2026-09-01',
    },
    {
        id: 2,
        competitor: 'Cahaya Pelita',
        product: 'Allspice Ground',
        qty: 100,
        price: 140000,
        division: 'INDUSTRY',
        notes: 'Kemas karung 25kg sak',
        recordedAt: '2026-08-28',
    },
    {
        id: 3,
        competitor: 'Cahaya Pelita',
        product: 'Paprika Powder 120 ASTA',
        qty: 300,
        price: 108000,
        division: 'INDUSTRY',
        notes: 'Impor China / Spanyol',
        recordedAt: '2026-09-03',
    },
    {
        id: 4,
        competitor: 'Duta Bumbu Sejahtera',
        product: 'Cinnamon Cassia Vera Powder',
        qty: 150,
        price: 88000,
        division: 'LOW COST',
        notes: 'Kualitas grade B non-sterilized',
        recordedAt: '2026-08-25',
    },
];