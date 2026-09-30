import type { UserRole } from '@/types';

interface RoleBadgeProps {
    role: UserRole;
    className?: string;
}

const roleStyles: Record<UserRole, string> = {
    INDUSTRY: 'bg-[#F1E4FF] text-[#6C28C9]',
    SME: 'bg-[#DDE9FF] text-[#2859BD]',
    'LOW COST': 'bg-[#FFF0C9] text-[#946000]',
    ALL: 'bg-[#DDF5E7] text-[#237B4B]',
};

export default function RoleBadge({
    role,
    className = '',
}: RoleBadgeProps) {
    return (
        <span
            className={`
                inline-flex items-center
                rounded-md px-2.5 py-1
                text-[10px] font-bold
                ${roleStyles[role]}
                ${className}
            `}
        >
            {role}
        </span>
    );
}