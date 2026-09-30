import {
    Building2,
    Package,
    Swords,
} from 'lucide-react';

import type {
    DatabaseTab,
} from '../../types/databaseCenter';

interface Props {
    activeTab: DatabaseTab;
    onChange: (tab: DatabaseTab) => void;
}

export default function DatabaseTabs({
    activeTab,
    onChange,
}: Props) {
    const tabs = [
        {
            key: 'customer' as DatabaseTab,
            label: 'Database Customer',
            icon: Building2,
        },
        {
            key: 'product' as DatabaseTab,
            label: 'Database Produk',
            icon: Package,
        },
        {
            key: 'competitor' as DatabaseTab,
            label: 'Database Kompetitor',
            icon: Swords,
        },
    ];

    return (
        <div className="flex flex-wrap gap-2">
            {tabs.map((tab) => {
                const Icon = tab.icon;
                const active =
                    activeTab === tab.key;

                return (
                    <button
                        key={tab.key}
                        type="button"
                        onClick={() =>
                            onChange(tab.key)
                        }
                        className={`
                            flex items-center gap-2
                            rounded-lg
                            border
                            px-4 py-2.5
                            text-sm font-semibold
                            transition
                            ${
                                active
                                    ? 'border-[#19875f] bg-[#19875f] text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                            }
                        `}
                    >
                        <Icon size={17} />

                        {tab.label}
                    </button>
                );
            })}
        </div>
    );
}