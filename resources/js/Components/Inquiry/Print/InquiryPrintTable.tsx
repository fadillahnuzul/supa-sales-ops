import {
    InquiryPrintListItem,
} from "./../../../Services/InquiryPrintApi";


interface Props {
    inquiries:
        InquiryPrintListItem[];

    selectedId:
        number | null;

    onSelect:
        (id: number) => void;
}


export default function InquiryPrintTable({
    inquiries,
    selectedId,
    onSelect,
}: Props) {

    return (
        <div className="bg-white border rounded-xl overflow-hidden">

            <div className="p-5 border-b">
                <h3 className="font-semibold text-lg">
                    Select Inquiry
                </h3>
            </div>


            <div className="overflow-x-auto">

                <table className="w-full text-sm">

                    <thead className="bg-gray-50">
                        <tr>
                            <th className="p-3">
                                Select
                            </th>

                            <th className="p-3 text-left">
                                Inquiry
                            </th>

                            <th className="p-3 text-left">
                                Date
                            </th>

                            <th className="p-3 text-left">
                                Customer
                            </th>

                            <th className="p-3 text-left">
                                PIC
                            </th>

                            <th className="p-3 text-center">
                                Items
                            </th>
                        </tr>
                    </thead>


                    <tbody>

                        {inquiries.map(
                            (item) => (

                                <tr
                                    key={item.id}
                                    onClick={() =>
                                        onSelect(
                                            item.id
                                        )
                                    }
                                    className={`
                                        border-t
                                        cursor-pointer
                                        hover:bg-green-50
                                        ${
                                            selectedId
                                            ===
                                            item.id
                                                ?
                                                "bg-green-50"
                                                :
                                                ""
                                        }
                                    `}
                                >

                                    <td className="p-3 text-center">

                                        <input
                                            type="radio"
                                            checked={
                                                selectedId
                                                ===
                                                item.id
                                            }
                                            readOnly
                                        />

                                    </td>


                                    <td className="p-3 font-medium">

                                        {
                                            item.code
                                        }

                                    </td>


                                    <td className="p-3">

                                        {
                                            item.date
                                        }

                                    </td>


                                    <td className="p-3">

                                        {
                                            item.customer_name
                                            ?? "-"
                                        }

                                    </td>


                                    <td className="p-3">

                                        {
                                            item.pic
                                            ?? "-"
                                        }

                                    </td>


                                    <td className="p-3 text-center">

                                        {
                                            item.total_items
                                        }

                                    </td>

                                </tr>

                            )
                        )}

                    </tbody>

                </table>

            </div>

        </div>
    );
}