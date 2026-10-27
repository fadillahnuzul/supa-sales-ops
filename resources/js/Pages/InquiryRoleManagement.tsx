import { Head } from '@inertiajs/react';
import {
  Search,
  Plus,
  Pencil,
  ChevronDown,
  ChevronRight,
  Users,
  ShieldCheck,
} from 'lucide-react';

import { useMemo, useState } from 'react';

import AuthenticatedLayout from '../Layouts/AuthenticatedLayout';
import UserRoleModal from '../Components/Modal/UserRoleModal';

// Sesuaikan import path dengan struktur folder Anda.

interface EmployeeOption {
  id: number;
  name: string;
  username?: string | null;
  division?: string | null;
}

interface RoleOption {
  id: number;
  name: string;
}

interface SegmentationOption {
  id: number;
  name: string;
}

export interface UserOption {
  id: number;
  employee_id: number;
  name: string;
  username: string | null;
  role_id: number | null;
  role_name: string | null;
  segmentation_ids: number[];
  segmentation_names: string[];
}

interface Props {
  users: UserOption[];
  employeeList: EmployeeOption[];
  roles: RoleOption[];
  segmentation: SegmentationOption[];
}

export default function InquiryRoleManagement({
  users = [],
  employeeList = [],
  roles = [],
  segmentation = [],
}: Props) {
  const [search, setSearch] = useState('');
  const [openedId, setOpenedId] = useState<number | null>(null);

  const [inquiryModal, setInquiryModal] = useState(false);
  const [selectedUser, setSelectedUser] = useState<UserOption | null>(null);

  // SEARCH
  const filteredData = useMemo(() => {
    const keyword = search.trim().toLowerCase();

    if (!keyword) return users;

    return users.filter((user) => {
      const searchable = [
        user.name,
        user.username,
        user.role_name,
        ...(user.segmentation_names ?? []),
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

      return searchable.includes(keyword);
    });
  }, [search, users]);

  // MODAL
  function handleAdd() {
    setSelectedUser(null);
    setInquiryModal(true);
  }

  function handleEdit(user: UserOption) {
    setSelectedUser(user);
    setInquiryModal(true);
  }

  function handleClose() {
    setInquiryModal(false);
    setSelectedUser(null);
  }

  function toggleRow(id: number) {
    setOpenedId((current) =>
      current === id ? null : id
    );
  }

  return (
    <>
      <Head title="Inquiry Role Management" />

      <AuthenticatedLayout>
        <div className="min-h-full bg-[#f6f7f8] p-6">
          <div className="mx-auto max-w-[1700px] space-y-5">

            {/* HEADER */}
            <div className="flex items-center gap-3">
              <div className="rounded-xl bg-[#19875f]/10 p-3">
                <ShieldCheck
                  size={24}
                  className="text-[#19875f]"
                />
              </div>

              <div>
                <h1 className="text-xl font-bold text-gray-900">
                  Inquiry Role Management
                </h1>
                <p className="text-sm text-gray-500">
                  Manage user roles and sales segmentation
                </p>
              </div>
            </div>

            {/* SEARCH & ADD */}
            <div className="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">

              <div className="relative w-full max-w-xl">
                <Search
                  size={17}
                  className="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"
                />

                <input
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  placeholder="Cari nama, username, role, atau segmentation..."
                  className="h-11 w-full rounded-lg border border-gray-300 pl-11 pr-4 text-sm outline-none transition focus:border-[#19875f] focus:ring-2 focus:ring-[#19875f]/10"
                />
              </div>

              <button
                type="button"
                onClick={handleAdd}
                className="flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-[#19875f] px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#146e4e]"
              >
                <Plus size={17} />
                Tambah User
              </button>
            </div>

            {/* TABLE */}
            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

              <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <div className="flex items-center gap-2">
                  <Users
                    size={18}
                    className="text-[#19875f]"
                  />
                  <h2 className="font-semibold text-gray-800">
                    User Access List
                  </h2>
                </div>

                <span className="text-sm text-gray-500">
                  {filteredData.length} Users
                </span>
              </div>

              <div className="overflow-x-auto">
                <table className="w-full min-w-[850px] text-left text-sm">
                  <thead className="bg-gray-50 text-xs uppercase text-gray-600">
                    <tr className="border-b border-gray-200">
                      <th className="w-10 px-3 py-3" />
                      <th className="px-4 py-3">Nama</th>
                      <th className="px-4 py-3">Username</th>
                      <th className="px-4 py-3">Role</th>
                      <th className="px-4 py-3">Segmentation</th>
                      <th className="px-4 py-3 text-center">
                        Action
                      </th>
                    </tr>
                  </thead>

                  <tbody className="divide-y divide-gray-100">
                    {filteredData.map((user) => (
                      <>
                        <tr
                          key={`user-${user.id}`}
                          className="transition hover:bg-gray-50"
                        >
                          <td className="px-3 py-3">
                            <button
                              type="button"
                              onClick={() => toggleRow(user.id)}
                              className="text-gray-400 hover:text-[#19875f]"
                            >
                            </button>
                          </td>

                          <td className="px-4 py-3 font-medium text-gray-900">
                            {user.name}
                          </td>

                          <td className="px-4 py-3 text-gray-600">
                            {user.username || '-'}
                          </td>

                          <td className="px-4 py-3">
                            {user.role_name ? (
                              <span className="inline-flex rounded-md bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                                {user.role_name}
                              </span>
                            ) : (
                              <span className="text-gray-400">
                                No Role
                              </span>
                            )}
                          </td>

                          <td className="px-4 py-3">
                            <div className="flex flex-wrap gap-1.5">
                              {user.segmentation_names?.length ? (
                                user.segmentation_names.map((name, index) => (
                                  <span
                                    key={`${user.id}-seg-${index}`}
                                    className="rounded-md bg-gray-100 px-2.5 py-1 text-xs text-gray-700"
                                  >
                                    {name}
                                  </span>
                                ))
                              ) : (
                                <span className="text-gray-400">
                                  -
                                </span>
                              )}
                            </div>
                          </td>

                          <td className="px-4 py-3 text-center">
                            <button
                              type="button"
                              onClick={() => handleEdit(user)}
                              className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:border-[#19875f] hover:text-[#19875f]"
                            >
                              <Pencil size={14} />
                              Edit
                            </button>
                          </td>
                        </tr>
                      </>
                    ))}

                    {filteredData.length === 0 && (
                      <tr>
                        <td
                          colSpan={6}
                          className="px-4 py-4 text-center text-gray-400"
                        >
                          Tidak ada data user yang ditemukan.
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>

        {/* ADD / EDIT MODAL */}
        <UserRoleModal
          open={inquiryModal}
          onClose={handleClose}
          user={selectedUser}
          employees={employeeList}
          roles={roles}
          segmentation={segmentation}
        />
      </AuthenticatedLayout>
    </>
  );
}