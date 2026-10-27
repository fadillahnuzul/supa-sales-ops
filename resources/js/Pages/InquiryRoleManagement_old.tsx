import { Head } from '@inertiajs/react';
import React, { useCallback, useEffect, useMemo, useState } from "react";
import "../../css/InquiryRoleManagement.css";
import AuthenticatedLayout
  from './../Layouts/AuthenticatedLayout';

/**
 * Expected API (adapt route names / response mapping to your project):
 * GET /api/inquiry/roles/overview -> { employees: Employee[], segmentations: Segmentation[] }
 * PUT /api/inquiry/roles/employees/:employeeId -> { canAccessInquiry, role, segmentationIds }
 *
 * Employee is the employee joined with its Inquiry permission and EmployeeSegmentationModel.
 * segmentations comes from SegmentationModel. The backend must verify admin permission.
 */
const API_ROOT = "/api/inquiry/roles";

type Role = "admin" | "sales" | "user";
type Segmentation = { id: number | string; name: string };
type Employee = {
  id: number | string;
  name: string;
  email?: string | null;
  department?: string | null;
  canAccessInquiry: boolean;
  role: Role;
  segmentationIds: Array<string | number>;
};
type Overview = { employees: Employee[]; segmentations: Segmentation[] };
type Draft = Pick<Employee, "canAccessInquiry" | "role" | "segmentationIds">;

function normalizeOverview(raw: Overview): Overview {
  return {
    segmentations: (raw.segmentations || []).map((s) => ({ id: s.id, name: s.name })),
    employees: (raw.employees || []).map((e) => ({
      ...e,
      canAccessInquiry: Boolean(e.canAccessInquiry),
      role: ["admin", "sales", "user"].includes(e.role) ? e.role : "user",
      segmentationIds: (e.segmentationIds || []).map(String),
    })),
  };
}

export default function InquiryRoleManagement() {
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [segmentations, setSegmentations] = useState<Segmentation[]>([]);
  const [drafts, setDrafts] = useState<Record<string, Draft>>({});
  const [loading, setLoading] = useState(true);
  const [savingId, setSavingId] = useState<string | null>(null);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [search, setSearch] = useState("");
  const [filter, setFilter] = useState<"all" | "enabled" | "disabled" | "sales">("all");

  const load = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const response = await fetch(`${API_ROOT}/overview`, { credentials: "include" });
      if (!response.ok) throw new Error(`Gagal mengambil data (${response.status})`);
      const data = normalizeOverview((await response.json()) as Overview);
      setEmployees(data.employees);
      setSegmentations(data.segmentations);
      setDrafts({});
    } catch (e) {
      setError(e instanceof Error ? e.message : "Gagal memuat data");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void load(); }, [load]);

  const getDraft = (e: Employee): Draft => drafts[String(e.id)] || {
    canAccessInquiry: e.canAccessInquiry,
    role: e.role,
    segmentationIds: e.segmentationIds.map(String),
  };

  const setDraft = (e: Employee, next: Draft) => {
    setMessage("");
    setDrafts((old) => ({ ...old, [String(e.id)]: next }));
  };

  const isChanged = (e: Employee) => {
    const d = getDraft(e);
    return d.canAccessInquiry !== e.canAccessInquiry || d.role !== e.role ||
      JSON.stringify([...d.segmentationIds].map(String).sort()) !==
      JSON.stringify([...e.segmentationIds].map(String).sort());
  };

  const save = async (e: Employee) => {
    const id = String(e.id);
    const d = getDraft(e);
    if (d.canAccessInquiry && d.role === "sales" && !d.segmentationIds.length) {
      setError(`Pilih minimal satu segmentasi untuk Sales: ${e.name}`);
      return;
    }
    setSavingId(id);
    setError("");
    setMessage("");
    try {
      const response = await fetch(`${API_ROOT}/employees/${encodeURIComponent(id)}`, {
        method: "PUT",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          canAccessInquiry: d.canAccessInquiry,
          role: d.role,
          segmentationIds: d.canAccessInquiry && d.role === "sales" ? d.segmentationIds : [],
        }),
      });
      if (!response.ok) {
        const payload = await response.json().catch(() => null);
        throw new Error(payload?.message || `Gagal menyimpan (${response.status})`);
      }
      // Backend may normalize the payload; use its returned employee if supplied.
      const result = await response.json().catch(() => null);
      const updated: Employee = result?.employee || {
        ...e, ...d,
        segmentationIds: d.canAccessInquiry && d.role === "sales" ? d.segmentationIds : [],
      };
      setEmployees((prev) => prev.map((item) => String(item.id) === id ? {
        ...updated, segmentationIds: (updated.segmentationIds || []).map(String),
      } : item));
      setDrafts((prev) => { const copy = { ...prev }; delete copy[id]; return copy; });
      setMessage(`Perubahan untuk ${e.name} berhasil disimpan.`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Tidak dapat menyimpan perubahan");
    } finally {
      setSavingId(null);
    }
  };

  const visible = useMemo(() => employees.filter((e) => {
    const q = search.trim().toLowerCase();
    const matches = !q || [e.name, e.email, e.department].some((x) => (x || "").toLowerCase().includes(q));
    const d = drafts[String(e.id)] || e;
    return matches && (filter === "all" ||
      (filter === "enabled" && d.canAccessInquiry) ||
      (filter === "disabled" && !d.canAccessInquiry) ||
      (filter === "sales" && d.role === "sales"));
  }), [employees, drafts, search, filter]);

  const activeCount = employees.filter((e) => e.canAccessInquiry).length;
  const salesCount = employees.filter((e) => e.canAccessInquiry && e.role === "sales").length;

  return (
    <AuthenticatedLayout>
      <Head
        title="Role Management"
      />
      <main className="irm-page">
        <div className="irm-header">
          <div><div className="irm-eyebrow">INQUIRY SYSTEM / ADMINISTRATION</div><h1>Role Management</h1>
            <p>Kelola akses sistem dan segmentasi Sales berdasarkan data karyawan.</p></div>
          <button className="irm-button irm-secondary" onClick={() => void load()} disabled={loading || savingId !== null}>↻ Refresh</button>
        </div>
        <section className="irm-panel">
          <div className="irm-toolbar">
            <label className="irm-search"><span className="irm-sr">Cari karyawan</span><input placeholder="Cari nama, email, atau departemen..." value={search} onChange={(e) => setSearch(e.target.value)} /></label>
            <select aria-label="Filter karyawan" value={filter} onChange={(e) => setFilter(e.target.value as typeof filter)}>
              <option value="all">Semua karyawan</option><option value="enabled">Akses aktif</option>
              <option value="disabled">Akses nonaktif</option><option value="sales">Role Sales</option>
            </select>
          </div>
          {error && <div className="irm-alert irm-error" role="alert">{error}</div>}
          {message && <div className="irm-alert irm-success" role="status">{message}</div>}
          {loading ? <div className="irm-empty">Memuat data karyawan dan segmentasi...</div> :
            <div className="irm-table-scroll"><table className="irm-table"><thead><tr>
              <th>Karyawan</th><th>Departemen</th><th>Akses Inquiry</th><th>Role</th><th>Segmentasi Sales</th><th>Tindakan</th>
            </tr></thead><tbody>
                {visible.map((e) => {
                  const d = getDraft(e);
                  const busy = savingId === String(e.id);
                  const canEditSegmentation = d.canAccessInquiry && d.role === "sales";
                  return <tr key={String(e.id)}>
                    <td><strong>{e.name}</strong><small>{e.email || "—"}</small></td>
                    <td>{e.department || "—"}</td>
                    <td><label className="irm-switch"><input type="checkbox" checked={d.canAccessInquiry} disabled={!!savingId}
                      onChange={(event) => setDraft(e, { ...d, canAccessInquiry: event.target.checked })} /><span>{d.canAccessInquiry ? "Aktif" : "Nonaktif"}</span></label></td>
                    <td><select value={d.role} disabled={!d.canAccessInquiry || !!savingId} onChange={(event) => {
                      const role = event.target.value as Role;
                      setDraft(e, { ...d, role, segmentationIds: role === "sales" ? d.segmentationIds : [] });
                    }}><option value="user">User</option><option value="sales">S ales</option><option value="admin">Admin</option></select></td>
                    <td><div className="irm-segments">
                      {canEditSegmentation ? segmentations.length ? segmentations.map((s) => {
                        const id = String(s.id);
                        return <label className="irm-chip" key={id}><input type="checkbox" checked={d.segmentationIds.map(String).includes(id)} disabled={!!savingId}
                          onChange={(event) => setDraft(e, { ...d, segmentationIds: event.target.checked ? [...d.segmentationIds, id] : d.segmentationIds.filter((v) => String(v) !== id) })} />{s.name}</label>;
                      }) : <span className="irm-muted">Segmentasi belum tersedia</span> : <span className="irm-muted">Khusus role Sales aktif</span>}
                    </div></td>
                    <td><div className="irm-actions"><button className="irm-button irm-primary" disabled={!isChanged(e) || !!savingId} onClick={() => void save(e)}>{busy ? "Menyimpan..." : "Simpan"}</button>
                      {isChanged(e) && <button className="irm-button irm-text" disabled={!!savingId} onClick={() => setDrafts((prev) => { const next = { ...prev }; delete next[String(e.id)]; return next; })}>Batal</button>}</div></td>
                  </tr>;
                })}
                {!visible.length && <tr className="py-0"><td colSpan={6} className="irm-empty">Tidak ada karyawan yang sesuai filter.</td></tr>}
              </tbody></table></div>}
        </section>
        <p className="irm-footnote">Perubahan hanya disimpan setelah tombol <b>Simpan</b> ditekan. Admin perlu memiliki otorisasi di backend; menyembunyikan menu saja tidak cukup untuk mengamankan akses.</p>
      </main>
    </AuthenticatedLayout>
  );
}
