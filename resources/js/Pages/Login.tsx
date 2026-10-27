import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Eye, EyeOff, LockKeyhole, ArrowRight } from 'lucide-react';

export default function Login() {
    const [visible, setVisible] = useState(false);
    const form = useForm({ username: '', password: '' });
    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post('/login', { onFinish: () => form.reset('password') });
    }
    return <>
        <Head title="Login — SUPA Sales Operations" />
        <main className="grid min-h-screen bg-[#f7f7f2] lg:grid-cols-2">
            <section className="relative hidden flex-col justify-between overflow-hidden bg-[#0c513c] p-14 text-white lg:flex">
                <div className="text-3xl font-black tracking-[0.2em]">SUPA<span className="mt-2 block text-xs font-normal tracking-[0.16em]">SURYA NIAGA</span></div>
                <div className="relative z-10 max-w-lg"><p className="mb-5 text-xs font-semibold uppercase tracking-[0.25em] text-[#c8dcaa]">Sales Operations</p><h1 className="text-5xl font-semibold leading-tight">From Indonesia<br />to the World.</h1><p className="mt-6 max-w-sm leading-relaxed text-green-100/80">Kelola inquiry, data pelanggan, dan quotation dalam satu ruang kerja.</p></div>
                <p className="text-sm text-green-100/70">Know Your Source · PT SUPA SURYA NIAGA</p>
                <div aria-hidden="true" className="absolute -bottom-40 -right-40 h-[32rem] w-[32rem] rounded-full border-[70px] border-white/5" />
            </section>
            <section className="flex items-center justify-center px-6 py-14 sm:px-12">
                <div className="w-full max-w-md">
                    <p className="mb-10 text-2xl font-black tracking-widest text-[#0c513c] lg:hidden">SUPA</p>
                    <div className="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e5eddf] text-[#0c513c]"><LockKeyhole size={23} /></div>
                    <h2 className="text-3xl font-semibold tracking-tight text-gray-900">Selamat datang kembali</h2>
                    <p className="mt-3 text-sm leading-relaxed text-gray-500">Masuk dengan akun perusahaan untuk melanjutkan.</p>
                    <form onSubmit={submit} className="mt-9 space-y-5">
                        <div><label htmlFor="username" className="mb-2 block text-sm font-medium text-gray-700">Username</label><input id="username" name="username" type="text" required autoFocus autoComplete="username" value={form.data.username} onChange={e => form.setData('username', e.target.value)} aria-invalid={!!form.errors.username} aria-describedby={form.errors.username ? 'username-error' : undefined} className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 outline-none focus:border-[#19875f] focus:ring-2 focus:ring-[#19875f]/15" placeholder="Masukkan Username" />{form.errors.username && <p id="username-error" role="alert" className="mt-2 text-sm text-red-600">{form.errors.username}</p>}</div>
                        <div><label htmlFor="password" className="mb-2 block text-sm font-medium text-gray-700">Kata sandi</label><div className="relative"><input id="password" name="password" type={visible ? 'text' : 'password'} required autoComplete="current-password" value={form.data.password} onChange={e => form.setData('password', e.target.value)} aria-invalid={!!form.errors.password} className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-12 outline-none focus:border-[#19875f] focus:ring-2 focus:ring-[#19875f]/15" placeholder="Masukkan Kata Sandi" /><button type="button" onClick={() => setVisible(!visible)} aria-label={visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'} aria-pressed={visible} className="absolute inset-y-0 right-0 px-4 text-gray-500">{visible ? <EyeOff size={18} /> : <Eye size={18} />}</button></div>{form.errors.password && <p role="alert" className="mt-2 text-sm text-red-600">{form.errors.password}</p>}</div>
                        <button type="submit" disabled={form.processing} className="flex w-full items-center justify-center gap-3 rounded-xl bg-[#147957] px-4 py-3.5 font-semibold text-white transition hover:bg-[#0c513c] disabled:opacity-60">{form.processing ? 'Memproses…' : 'Masuk'}<ArrowRight size={18} /></button>
                    </form>
                    <p className="mt-6 text-center text-sm leading-relaxed text-gray-500">Gunakan akun employee Anda.<br /> Lupa kata sandi? Hubungi Digicom ya!</p>
                    <p className="mt-12 text-center text-xs text-gray-400">PT SUPA SURYA NIAGA · Sales Operations</p>
                </div>
            </section>
        </main>
    </>;
}
