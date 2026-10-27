import { router } from '@inertiajs/react';
import { X, Save } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Option {
  id: number;
  name: string;
}

interface EmployeeOption extends Option {
  first_name?: string | null;
  last_name?: string | null;
  username?: string | null;
  division?: string | null;
}

interface UserOption {
  id: number;
  employee_id: number;
  name: string;
  username: string | null;
  role_id: number | null;
  segmentation_ids: number[];
}

interface Props {
  open: boolean;
  onClose: () => void;
  user: UserOption | null;
  employees: EmployeeOption[];
  roles: Option[];
  segmentation: Option[];
}

export default function UserRoleModal({
  open,
  onClose,
  user,
  employees,
  roles,
  segmentation,
}: Props) {
  const [employeeId, setEmployeeId] = useState('');
  const [roleId, setRoleId] = useState('');
  const [segmentationIds, setSegmentationIds] = useState<number[]>([]);
  const [processing, setProcessing] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const isEdit = user !== null;

  useEffect(() => {
    if (!open) return;

    setEmployeeId(user?.employee_id?.toString() ?? '');
    setRoleId(user?.role_id?.toString() ?? '');
    setSegmentationIds(user?.segmentation_ids ?? []);
    setErrors({});
  }, [open, user]);

  function toggleSegmentation(id: number) {
    setSegmentationIds((current) =>
      current.includes(id)
        ? current.filter((item) => item !== id)
        : [...current, id]
    );
  }

  function handleSave() {
    if (processing) return;

    const payload = {
      employee_id: Number(employeeId),
      role_id: Number(roleId),
      segmentation_ids: segmentationIds,
    };

    const options = {
      preserveScroll: true,
      onStart: () => setProcessing(true),
      onFinish: () => setProcessing(false),
      onSuccess: () => onClose(),
      onError: (validationErrors: Record<string, string>) => {
        setErrors(validationErrors);
      },
    };

    if (isEdit) {
      router.put(
        `/role-management/${employeeId}`,
        payload,
        options
      );
    } else {
      router.post(
        '/role-management',
        payload,
        options
      );
    }
  }

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="user-role-modal-title"
        className="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-xl"
      >

        {/* HEADER */}
        <div className="flex items-center justify-between border-b border-gray-200 px-6 py-5">
          <div>
            <h2
              id="user-role-modal-title"
              className="text-lg font-bold text-gray-900"
            >
              {isEdit ? 'Edit User Access' : 'Tambah User Access'}
            </h2>
            <p className="mt-1 text-sm text-gray-500">
              Manage employee role and segmentation
            </p>
          </div>

          <button
            type="button"
            onClick={onClose}
            disabled={processing}
            aria-label="Tutup modal"
            className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700"
          >
            <X size={20} />
          </button>
        </div>

        {/* BODY */}
        <div className="max-h-[65vh] space-y-5 overflow-y-auto px-6 py-5">

          {/* EMPLOYEE */}
          <div>
            <label className="mb-2 block text-sm font-semibold text-gray-700">
              Employee <span className="text-red-500">*</span>
            </label>

            <select
              value={employeeId}
              onChange={(e) => setEmployeeId(e.target.value)}
              disabled={processing || isEdit}
              className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-[#19875f] disabled:bg-gray-100"
            >
              <option value="">Pilih Employee</option>

              {employees.map((emp) => (
                <option key={emp.id} value={emp.id}>
                  {[emp.first_name, emp.last_name]
                    .filter(Boolean)
                    .join(' ') || emp.name}
                </option>
              ))}
            </select>

            {errors.employee_id && (
              <p className="mt-1 text-xs text-red-600">
                {errors.employee_id}
              </p>
            )}
          </div>

          {/* ROLE */}
          <div>
            <label className="mb-2 block text-sm font-semibold text-gray-700">
              Role <span className="text-red-500">*</span>
            </label>

            <select
              value={roleId}
              onChange={(e) => setRoleId(e.target.value)}
              className="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm outline-none focus:border-[#19875f]"
            >
              <option value="">Pilih Role</option>
              {roles.map((role) => (
                <option key={role.id} value={role.id}>
                  {role.name}
                </option>
              ))}
            </select>

            {errors.role_id && (
              <p className="mt-1 text-xs text-red-600">
                {errors.role_id}
              </p>
            )}
          </div>

          {/* SEGMENTATION */}
          <div>
            <label className="mb-2 block text-sm font-semibold text-gray-700">
              Segmentation
            </label>

            <div className="max-h-52 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3">
              {segmentation.map((item) => (
                <label
                  key={item.id}
                  className="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 transition hover:bg-gray-50"
                >
                  <input
                    type="checkbox"
                    checked={segmentationIds.includes(item.id)}
                    onChange={() => toggleSegmentation(item.id)}
                    className="h-4 w-4 accent-[#19875f]"
                  />

                  <span className="text-sm text-gray-700">
                    {item.name}
                  </span>
                </label>
              ))}

              {segmentation.length === 0 && (
                <p className="py-3 text-center text-sm text-gray-400">
                  Belum ada segmentation.
                </p>
              )}
            </div>

            <p className="mt-2 text-xs text-gray-500">
              Pilih satu atau beberapa segmentation untuk user.
            </p>

            {errors.segmentation_ids && (
              <p className="mt-1 text-xs text-red-600">
                {errors.segmentation_ids}
              </p>
            )}
          </div>
        </div>

        {/* FOOTER */}
        <div className="flex justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">
          <button
            type="button"
            onClick={onClose}
            disabled={processing}
            className="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-100"
          >
            Cancel
          </button>

          <button
            type="button"
            onClick={handleSave}
            disabled={processing || !employeeId || !roleId}
            className="inline-flex items-center gap-2 rounded-lg bg-[#19875f] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#146e4e] disabled:cursor-not-allowed disabled:opacity-50"
          >
            <Save size={16} />
            {processing
              ? 'Saving...'
              : isEdit
                ? 'Update User'
                : 'Save User'}
          </button>
        </div>
      </div>
    </div>
  );
}