interface Props {
    day: string;
    month: string;
    year: string;
    search: string;

    onDayChange: (
        value: string
    ) => void;

    onMonthChange: (
        value: string
    ) => void;

    onYearChange: (
        value: string
    ) => void;

    onSearchChange: (
        value: string
    ) => void;
}

export default function InquiryPrintFilter({
    day,
    month,
    year,
    search,
    onDayChange,
    onMonthChange,
    onYearChange,
    onSearchChange,
}: Props) {

    const years = [];

    const currentYear =
        new Date().getFullYear();

    for (
        let y = currentYear;
        y >= currentYear - 5;
        y--
    ) {
        years.push(y);
    }


    return (
        <div className="bg-white rounded-xl border p-5">

            <h3 className="font-semibold text-lg mb-4">
                Filter Inquiry
            </h3>


            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">

                {/* DAY */}

                <div>
                    <label className="text-sm font-medium">
                        Date
                    </label>

                    <select
                        value={day}
                        onChange={(e) =>
                            onDayChange(
                                e.target.value
                            )
                        }
                        className="w-full border rounded-lg px-3 py-2 mt-1"
                    >
                        <option value="">
                            All
                        </option>

                        {Array.from(
                            { length: 31 },
                            (_, i) => i + 1
                        ).map((d) => (
                            <option
                                key={d}
                                value={d}
                            >
                                {d}
                            </option>
                        ))}
                    </select>
                </div>


                {/* MONTH */}

                <div>
                    <label className="text-sm font-medium">
                        Month
                    </label>

                    <select
                        value={month}
                        onChange={(e) =>
                            onMonthChange(
                                e.target.value
                            )
                        }
                        className="w-full border rounded-lg px-3 py-2 mt-1"
                    >
                        <option value="">
                            All
                        </option>

                        {[
                            "January",
                            "February",
                            "March",
                            "April",
                            "May",
                            "June",
                            "July",
                            "August",
                            "September",
                            "October",
                            "November",
                            "December",
                        ].map(
                            (name, index) => (
                                <option
                                    key={name}
                                    value={index + 1}
                                >
                                    {name}
                                </option>
                            )
                        )}
                    </select>
                </div>


                {/* YEAR */}

                <div>
                    <label className="text-sm font-medium">
                        Year
                    </label>

                    <select
                        value={year}
                        onChange={(e) =>
                            onYearChange(
                                e.target.value
                            )
                        }
                        className="w-full border rounded-lg px-3 py-2 mt-1"
                    >
                        <option value="">
                            All
                        </option>

                        {years.map((y) => (
                            <option
                                key={y}
                                value={y}
                            >
                                {y}
                            </option>
                        ))}
                    </select>
                </div>


                {/* SEARCH */}

                <div>
                    <label className="text-sm font-medium">
                        Search
                    </label>

                    <input
                        value={search}
                        onChange={(e) =>
                            onSearchChange(
                                e.target.value
                            )
                        }
                        placeholder="Inquiry / Customer / PIC"
                        className="w-full border rounded-lg px-3 py-2 mt-1"
                    />
                </div>

            </div>
        </div>
    );
}