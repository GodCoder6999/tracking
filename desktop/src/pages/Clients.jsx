import { useEffect, useRef, useState } from 'react'
import api from '../api'
import { Centered, Spinner, PageHeader, fmtNum } from './Dashboard'

export default function ClientsPage() {
    const [clients, setClients] = useState([])
    const [loading, setLoading] = useState(true)
    const [search,  setSearch]  = useState('')
    const [modal,   setModal]   = useState(null)     // null | 'create' | client-obj
    const [showImport,  setShowImport]  = useState(false)
    const [showLedger,  setShowLedger]  = useState(false)

    function load() {
        setLoading(true)
        api.clients()
            .then(setClients)
            .finally(() => setLoading(false))
    }

    useEffect(() => { load() }, [])

    function onSaved(client, isNew) {
        setClients(prev => isNew
            ? [client, ...prev]
            : prev.map(c => c.id === client.id ? { ...c, ...client } : c)
        )
        setModal(null)
    }

    async function onDelete(client) {
        if (!confirm(`Delete ${client.name}? This cannot be undone.`)) return
        try {
            await api.clientDelete(client.id)
            setClients(prev => prev.filter(c => c.id !== client.id))
        } catch (err) {
            alert(err?.response?.data?.message || 'Delete failed.')
        }
    }

    const filtered = clients.filter(c =>
        c.name.toLowerCase().includes(search.toLowerCase()) ||
        c.email?.toLowerCase().includes(search.toLowerCase()) ||
        c.phone?.includes(search)
    )

    return (
        <div style={{ padding: 28 }}>
            <PageHeader
                title={`Clients (${clients.length})`}
                action={
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button onClick={() => setShowLedger(v => !v)} style={outlineBtn}>
                            {showLedger ? 'Hide Ledger' : '↓ Ledger PDF'}
                        </button>
                        <button onClick={() => setShowImport(v => !v)} style={outlineBtn}>
                            {showImport ? 'Hide Import' : '↑ Import'}
                        </button>
                        <button onClick={() => setModal('create')} style={btnStyle}>
                            + Add Client
                        </button>
                    </div>
                }
            />

            {showLedger && <LedgerPanel clients={clients} />}
            {showImport && <ImportPanel onDone={load} onClose={() => setShowImport(false)} />}

            <input
                placeholder="Search by name, email, phone…"
                value={search}
                onChange={e => setSearch(e.target.value)}
                style={{ padding: '8px 14px', fontSize: 13, border: '1px solid #e2e8f0', borderRadius: 8, marginBottom: 16, width: 300, outline: 'none' }}
            />

            <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e2e8f0', overflow: 'hidden' }}>
                {loading
                    ? <Centered><Spinner /></Centered>
                    : filtered.length === 0
                        ? <div style={{ padding: 40, textAlign: 'center', color: '#94a3b8', fontSize: 13 }}>No clients found.</div>
                        : (
                            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                                <thead>
                                    <tr style={{ borderBottom: '1px solid #f1f5f9' }}>
                                        {['Name', 'Email', 'Phone', 'Orders', 'Revenue', 'Due', ''].map(h => (
                                            <th key={h} style={thStyle}>{h}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {filtered.map(c => (
                                        <tr key={c.id}
                                            style={{ borderBottom: '1px solid #f8fafc' }}
                                            onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                                            onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                        >
                                            <td style={{ padding: '10px 16px', fontWeight: 600, color: '#0f172a' }}>
                                                {c.name}
                                                {c.address && <div style={{ fontSize: 11, color: '#94a3b8', fontWeight: 400, marginTop: 1 }}>{c.address}</div>}
                                            </td>
                                            <td style={tdStyle}>{c.email || '—'}</td>
                                            <td style={tdStyle}>{c.phone || '—'}</td>
                                            <td style={{ ...tdStyle, textAlign: 'center' }}>{c.orders_as_client_count ?? 0}</td>
                                            <td style={{ ...tdStyle, fontWeight: 600, color: '#0f172a' }}>
                                                {c.revenue ? `₹${fmtNum(c.revenue)}` : '—'}
                                            </td>
                                            <td style={{ ...tdStyle, color: (c.due_total ?? 0) > 0 ? '#dc2626' : '#64748b' }}>
                                                {c.due_total ? `₹${fmtNum(c.due_total)}` : '—'}
                                            </td>
                                            <td style={{ padding: '10px 16px', display: 'flex', gap: 10 }}>
                                                <button onClick={() => setModal(c)} style={actionBtn('#4f46e5')}>Edit</button>
                                                <button onClick={() => onDelete(c)} style={actionBtn('#ef4444')}>Delete</button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )
                }
            </div>

            {modal && (
                <ClientModal
                    client={modal === 'create' ? null : modal}
                    onSaved={onSaved}
                    onClose={() => setModal(null)}
                />
            )}
        </div>
    )
}

// ── Ledger Panel ──────────────────────────────────────────────────────────────

function LedgerPanel({ clients }) {
    const [from,     setFrom]     = useState('')
    const [to,       setTo]       = useState('')
    const [clientId, setClientId] = useState('')
    const [loading,  setLoading]  = useState(false)

    async function download() {
        setLoading(true)
        try {
            const params = {}
            if (from)     params.from      = from
            if (to)       params.to        = to
            if (clientId) params.client_id = clientId

            const blob = await api.ledger(params)
            const url  = URL.createObjectURL(blob)
            const a    = document.createElement('a')
            const clientName = clients.find(c => String(c.id) === String(clientId))?.name
            const label = clientName
                ? `ledger_${clientName.replace(/\s+/g, '_')}${from ? `_${from}` : ''}${to ? `_to_${to}` : ''}.pdf`
                : `dealer_ledger_${from || 'all'}${to ? `_to_${to}` : ''}.pdf`
            a.href     = url
            a.download = label
            a.click()
            URL.revokeObjectURL(url)
        } catch (err) {
            alert('Failed to download ledger.')
        } finally {
            setLoading(false)
        }
    }

    return (
        <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 10, padding: '14px 18px', marginBottom: 16, display: 'flex', gap: 12, alignItems: 'flex-end', flexWrap: 'wrap' }}>
            <div>
                <Label>From</Label>
                <input type="date" value={from} onChange={e => setFrom(e.target.value)} style={inp} />
            </div>
            <div>
                <Label>To</Label>
                <input type="date" value={to} onChange={e => setTo(e.target.value)} style={inp} />
            </div>
            <div>
                <Label>Client</Label>
                <select value={clientId} onChange={e => setClientId(e.target.value)} style={inp}>
                    <option value="">All Clients</option>
                    {clients.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
            </div>
            <button onClick={download} disabled={loading} style={btnStyle}>
                {loading ? 'Generating…' : '↓ Download PDF'}
            </button>
        </div>
    )
}

// ── Import Panel ──────────────────────────────────────────────────────────────

function ImportPanel({ onDone, onClose }) {
    const fileRef = useRef()
    const [result,  setResult]  = useState(null) // { created, skipped }
    const [loading, setLoading] = useState(false)
    const [error,   setError]   = useState('')

    async function submit(e) {
        e.preventDefault()
        const file = fileRef.current?.files?.[0]
        if (!file) return
        setLoading(true)
        setError('')
        setResult(null)
        try {
            const fd = new FormData()
            fd.append('file', file)
            const res = await api.clientImport(fd)
            setResult(res)
            if (res.created?.length) onDone()
        } catch (err) {
            setError(err?.response?.data?.message || 'Import failed.')
        } finally {
            setLoading(false)
        }
    }

    return (
        <div style={{ background: '#f8fafc', border: '1px solid #e2e8f0', borderRadius: 10, padding: 18, marginBottom: 16 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 12 }}>
                <span style={{ fontWeight: 600, fontSize: 13 }}>Import Clients (CSV or JSON)</span>
                <button onClick={onClose} style={{ background: 'none', border: 'none', color: '#94a3b8', cursor: 'pointer', fontSize: 16 }}>×</button>
            </div>
            <div style={{ fontSize: 12, color: '#64748b', marginBottom: 10 }}>
                CSV columns: <code>name, email, phone, address, password</code> — name &amp; email required, password optional (auto-generated if blank)
            </div>
            {error && <div style={{ background: '#fef2f2', border: '1px solid #fecaca', borderRadius: 6, padding: '8px 12px', color: '#dc2626', fontSize: 12, marginBottom: 10 }}>{error}</div>}
            <form onSubmit={submit} style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
                <input ref={fileRef} type="file" accept=".csv,.json" required style={{ fontSize: 13 }} />
                <button type="submit" disabled={loading} style={btnStyle}>{loading ? 'Importing…' : 'Import'}</button>
            </form>

            {result && (
                <div style={{ marginTop: 14 }}>
                    {result.created?.length > 0 && (
                        <>
                            <div style={{ fontWeight: 600, fontSize: 12, color: '#16a34a', marginBottom: 6 }}>Created ({result.created.length})</div>
                            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12, marginBottom: 10 }}>
                                <thead><tr style={{ borderBottom: '1px solid #e2e8f0' }}>
                                    <th style={thStyle}>Name</th><th style={thStyle}>Email</th><th style={thStyle}>Password</th>
                                </tr></thead>
                                <tbody>{result.created.map((c, i) => (
                                    <tr key={i}><td style={tdStyle}>{c.name}</td><td style={tdStyle}>{c.email}</td><td style={{ ...tdStyle, fontFamily: 'monospace', color: '#4f46e5' }}>{c.password}</td></tr>
                                ))}</tbody>
                            </table>
                        </>
                    )}
                    {result.skipped?.length > 0 && (
                        <>
                            <div style={{ fontWeight: 600, fontSize: 12, color: '#dc2626', marginBottom: 6 }}>Skipped ({result.skipped.length})</div>
                            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12 }}>
                                <thead><tr style={{ borderBottom: '1px solid #e2e8f0' }}>
                                    <th style={thStyle}>Row</th><th style={thStyle}>Data</th><th style={thStyle}>Reason</th>
                                </tr></thead>
                                <tbody>{result.skipped.map((s, i) => (
                                    <tr key={i}><td style={tdStyle}>{s.row}</td><td style={tdStyle}>{s.data}</td><td style={{ ...tdStyle, color: '#ef4444' }}>{s.reason}</td></tr>
                                ))}</tbody>
                            </table>
                        </>
                    )}
                </div>
            )}
        </div>
    )
}

// ── Client Modal ──────────────────────────────────────────────────────────────

function ClientModal({ client, onSaved, onClose }) {
    const isNew = !client
    const [form, setForm] = useState({
        name:     client?.name    || '',
        email:    client?.email   || '',
        phone:    client?.phone   || '',
        address:  client?.address || '',
        password: '',
    })
    const [error,   setError]   = useState('')
    const [loading, setLoading] = useState(false)

    function set(k, v) { setForm(f => ({ ...f, [k]: v })) }

    async function submit(e) {
        e.preventDefault()
        setError('')
        setLoading(true)
        try {
            const payload = { ...form }
            if (!payload.password) delete payload.password
            if (!isNew) delete payload.email  // email update goes through validation on backend but skip if unchanged
            let res
            if (isNew) {
                res = await api.clientCreate(payload)
            } else {
                res = await api.clientUpdate(client.id, payload)
            }
            onSaved(res, isNew)
        } catch (err) {
            const msgs = err?.response?.data?.errors
            setError(msgs ? Object.values(msgs).flat().join(' ') : (err?.response?.data?.message || 'Failed.'))
        } finally {
            setLoading(false)
        }
    }

    return (
        <Overlay onClose={onClose}>
            <div style={{ background: '#fff', borderRadius: 14, padding: 28, width: 460, maxWidth: '92vw' }} onClick={e => e.stopPropagation()}>
                <h2 style={{ fontSize: 16, fontWeight: 700, color: '#0f172a', marginBottom: 20 }}>{isNew ? 'Add Client' : 'Edit Client'}</h2>
                {error && <div style={{ background: '#fef2f2', border: '1px solid #fecaca', borderRadius: 7, padding: '10px 14px', color: '#dc2626', fontSize: 13, marginBottom: 14 }}>{error}</div>}
                <form onSubmit={submit} style={{ display: 'flex', flexDirection: 'column', gap: 14 }}>
                    <Field label="Name *" value={form.name} onChange={v => set('name', v)} required />
                    <Field label="Email *" type="email" value={form.email} onChange={v => set('email', v)} required={isNew} disabled={!isNew} />
                    <Field label="Phone" value={form.phone} onChange={v => set('phone', v)} />
                    <Field label="Address" value={form.address} onChange={v => set('address', v)} />
                    <Field
                        label={isNew ? 'Password *' : 'Password (blank = keep existing)'}
                        type="password"
                        value={form.password}
                        onChange={v => set('password', v)}
                        required={isNew}
                    />
                    <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end', marginTop: 4 }}>
                        <button type="button" onClick={onClose} style={{ padding: '8px 18px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#fff', color: '#475569', cursor: 'pointer', fontSize: 13 }}>Cancel</button>
                        <button type="submit" disabled={loading} style={{ ...btnStyle, opacity: loading ? 0.6 : 1 }}>{loading ? 'Saving…' : 'Save'}</button>
                    </div>
                </form>
            </div>
        </Overlay>
    )
}

// ── Shared exports ────────────────────────────────────────────────────────────

export function Overlay({ children, onClose }) {
    return (
        <div onClick={onClose} style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 100 }}>
            {children}
        </div>
    )
}

export function Field({ label, value, onChange, type = 'text', required, disabled }) {
    return (
        <div>
            <label style={{ display: 'block', fontSize: 11, fontWeight: 600, color: '#64748b', marginBottom: 5, textTransform: 'uppercase', letterSpacing: '0.04em' }}>{label}</label>
            <input type={type} value={value} onChange={e => onChange(e.target.value)} required={required} disabled={disabled}
                style={{ width: '100%', padding: '8px 12px', fontSize: 13, border: '1px solid #e2e8f0', borderRadius: 7, outline: 'none', boxSizing: 'border-box', background: disabled ? '#f8fafc' : '#fff', color: disabled ? '#94a3b8' : '#334155' }}
            />
        </div>
    )
}

function Label({ children }) {
    return <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 4, letterSpacing: '0.05em' }}>{children}</div>
}

const btnStyle    = { padding: '8px 18px', fontSize: 13, fontWeight: 600, borderRadius: 8, border: 'none', background: '#4f46e5', color: '#fff', cursor: 'pointer' }
const outlineBtn  = { padding: '7px 14px', fontSize: 12, fontWeight: 500, borderRadius: 7, border: '1px solid #e2e8f0', background: '#fff', color: '#475569', cursor: 'pointer' }
const inp         = { padding: '7px 10px', fontSize: 13, border: '1px solid #e2e8f0', borderRadius: 7, outline: 'none', background: '#fff', color: '#334155' }
const thStyle     = { padding: '9px 16px', textAlign: 'left', color: '#94a3b8', fontWeight: 600, fontSize: 11, textTransform: 'uppercase' }
const tdStyle     = { padding: '10px 16px', color: '#64748b' }
const actionBtn   = (color) => ({ fontSize: 11, color, background: 'none', border: 'none', cursor: 'pointer', fontWeight: 500 })
