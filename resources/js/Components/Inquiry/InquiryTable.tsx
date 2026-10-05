import InquiryAccordion from './InquiryAccordion';

import type {
    Inquiry,
} from '../../types/inquiry';

interface ProductOption {
    id: number;
    name: string;
    code: string;
    stdPrice: number | null;
}

interface GradeOption {
    id: number;
    name: string;
}

interface CompetitorOption {
    id: number;
    name: string;
    divisi?: string | null;
}

interface CompetitorPrice {
    id: number;

    competitorId: number;
    competitorName: string;
    productId: number;

    price: number | null;

    date: string | null;
    note?: string | null;
}

interface Props {
    data: Inquiry[];

    openedId: number | null;

    onToggle: (id: number) => void;

    products: ProductOption[];

    grades: GradeOption[];

    competitorPrices: CompetitorPrice[];
}

export default function InquiryTable({
    data,
    openedId,
    onToggle,
    products,
    grades,
    competitorPrices,
}: Props) {
    return (
        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
                <table className="w-full min-w-[1300px] text-left text-sm">
                    <thead>
                        <tr className="border-b border-gray-200">
                            <th className="w-10 px-4 py-4" />

                            <th className="px-4 py-4">
                                Inquiry Code
                            </th>

                            <th className="px-4 py-4">
                                Inquiry Date
                            </th>

                            <th className="px-4 py-4">
                                ETD
                            </th>

                            <th className="px-4 py-4">
                                PIC Sales
                            </th>

                            <th className="px-4 py-4">
                                Customer
                            </th>

                            <th className="px-4 py-4">
                                Shipping Rate
                            </th>

                            <th className="px-4 py-4">
                                Note
                            </th>

                            <th className="px-4 py-4 text-center">
                                Detail
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        {data.map(
                            (inquiry) => (
                                <InquiryAccordion
                                    key={
                                        inquiry.id
                                    }

                                    inquiry={
                                        inquiry
                                    }

                                    open={
                                        openedId ===
                                        inquiry.id
                                    }

                                    onToggle={() =>
                                        onToggle(
                                            inquiry.id
                                        )
                                    }

                                    products={
                                        products
                                    }

                                    grades={
                                        grades
                                    }

                                    competitorPrices={
                                        competitorPrices
                                    }
                                />
                            )
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}