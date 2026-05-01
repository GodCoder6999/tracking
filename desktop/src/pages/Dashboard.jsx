import { useEffect, useState } from 'react'
import api from '../api'
import OrdersPage    from './Orders'
import ClientsPage   from './Clients'
import ProductsPage  from './Products'
import AnalyticsPage from './Analytics'

const NAV = [
    { key: 'dashboard', label: 'Dashboard', icon: IconDash },
    { key: 'orders',    label: 'Orders',    icon: IconOrders },
    { key: 'clients',   label: 'Clients',   icon: IconClients },
    { key: 'products',  label: 'Products',  icon: IconProducts },
    { key: 'analytics', label: 'Analytics', icon: IconAnalytics },
]

export default function Dashboard({ dealer, onLogout }) {
    const [page, setPage] = useState('dashboard')

    return (
        <div style={{ display: 'flex', height: '100%', background: '#f8fafc' }}>
            {/* Sidebar */}
            <aside style={{
                width: 200, flexShrink: 0, background: '#0f172a',
                display: 'flex', flexDirection: 'column', borderRight: '1px solid #1e293b',
            }}>
                <div style={{ padding: '20px 16px 12px', borderBottom: '1px solid #1e293b' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                        <div style={{ width: 28, height: 28, background: '#4f46e5', borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#fff', fontWeight: 800, fontSize: 13 }}>T</div>
                        <span style={{ color: '#f1f5f9', fontWeight: 700, fontSize: 15 }}>Track</span>
                    </div>
                    <div style={{ marginTop: 8, color: '#64748b', fontSize: 11, fontWeight: 500 }}>{dealer.name}</div>
                </div>

                <nav style={{ flex: 1, padding: '8px 8px' }}>
                    {NAV.map(({ key, label, icon: Icon }) => (
                        <button key={key} onClick={() => setPage(key)} style={{
                            display: 'flex', alignItems: 'center', gap: 9, width: '100%',
                            padding: '8px 10px', borderRadius: 7, border: 'none', cursor: 'pointer',
                            marginBottom: 2, textAlign: 'left',
                            background: page === key ? '#1e293b' : 'transparent',
                            color: page === key ? '#e2e8f0' : '#64748b',
                            fontSize: 13, fontWeight: page === key ? 600 : 400,
                            transition: 'all 0.1s',
                        }}>
                            <Icon size={15} />
                            {label}
                        </button>
                    ))}
                </nav>

                <div style={{ padding: '12px 8px', borderTop: '1px solid #1e293b' }}>
                    <button onClick={onLogout} style={{
                        display: 'flex', alignItems: 'center', gap: 9, width: '100%',
                        padding: '8px 10px', borderRadius: 7, border: 'none', cursor: 'pointer',
                        background: 'transparent', color: '#475569', fontSize: 12,
                    }}>
                        <IconLogout size={14} />
                        Sign out
                    </button>
                </div>
            </aside>

            {/* Main content */}
            <main style={{ flex: 1, overflow: 'auto', minWidth: 0 }}>
                {page === 'dashboard' && <DashHome dealer={dealer} onNav={setPage} />}
                {page === 'orders'    && <OrdersPage dealer={dealer} />}
                {page === 'clients'   && <ClientsPage dealer={dealer} />}
                {page === 'products'  && <ProductsPage />}
                {page === 'analytics' && <AnalyticsPage />}
            </main>
        </div>
    )
}

// ── Home tab ──────────────────────────────────────────────────────────────────

function DashHome({ dealer, onNav }) {
    const [data, setData]       = useState(null)
    const [loading, setLoading] = useState(true)
    const [error, setError]     = useState('')

    useEffect(() => {
        api.dashboard()
            .then(setData)
            .catch(err => {
                const status = err?.response?.status
                const msg    = err?.response?.data?.message || err?.message || '?'
                setError(`[${status ?? 'network'}] ${msg}`)
            })
            .finally(() => setLoading(false))
    }, [])

    if (loading) return <Centered><Spinner /></Centered>
    if (error)   return <Centered><div style={{ color: '#ef4444', fontSize: 13 }}>{error}</div></Centered>

    const { stats, recent } = data

    return (
        <div style={{ padding: 28 }}>
            <h1 style={{ fontSize: 18, fontWeight: 700, color: '#0f172a', marginBottom: 20 }}>Dashboard</h1>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 14, marginBottom: 24 }}>
                <StatCard label="Clients"          value={stats.clients}          count />
                <StatCard label="Orders"           value={stats.orders}           count />
                <StatCard label="Pending Dispatch" value={stats.pending_dispatch} count />
                <StatCard label="Revenue"          value={stats.revenue} />
                <StatCard label="Received"         value={stats.received} />
                <StatCard label="Due"              value={stats.due} highlight={stats.due > 0} />
            </div>

            <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e2e8f0', overflow: 'hidden' }}>
                <div style={{ padding: '14px 20px', borderBottom: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <span style={{ fontWeight: 600, fontSize: 14, color: '#0f172a' }}>Recent Orders</span>
                    <button onClick={() => onNav('orders')} style={{ fontSize: 12, color: '#4f46e5', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 500 }}>View all →</button>
                </div>
                {recent.length === 0
                    ? <div style={{ padding: 32, textAlign: 'center', color: '#94a3b8', fontSize: 13 }}>No orders yet.</div>
                    : <OrderTable orders={recent} />
                }
            </div>
        </div>
    )
}

// ── Shared components ─────────────────────────────────────────────────────────

export function OrderTable({ orders, onSelect }) {
    return (
        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
            <thead>
                <tr style={{ borderBottom: '1px solid #f1f5f9' }}>
                    {['Order #', 'Client', 'Date', 'Amount', 'Payment', 'Dispatch'].map(h => (
                        <th key={h} style={{ padding: '8px 16px', textAlign: h === 'Amount' ? 'right' : 'left', color: '#94a3b8', fontWeight: 600, fontSize: 11, textTransform: 'uppercase', letterSpacing: '0.04em' }}>{h}</th>
                    ))}
                </tr>
            </thead>
            <tbody>
                {orders.map(o => (
                    <tr key={o.id}
                        onClick={() => onSelect?.(o)}
                        style={{ borderBottom: '1px solid #f8fafc', cursor: onSelect ? 'pointer' : 'default', transition: 'background 0.1s' }}
                        onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                        onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                    >
                        <td style={{ padding: '10px 16px', fontFamily: 'monospace', color: '#4f46e5', fontWeight: 600 }}>{o.order_number}</td>
                        <td style={{ padding: '10px 16px', color: '#334155' }}>{o.client?.name ?? '—'}</td>
                        <td style={{ padding: '10px 16px', color: '#64748b' }}>{fmtDate(o.order_date)}</td>
                        <td style={{ padding: '10px 16px', textAlign: 'right', fontWeight: 600, color: '#0f172a' }}>₹{fmtNum(o.total_amount)}</td>
                        <td style={{ padding: '10px 16px' }}><Badge status={o.payment_status} type="payment" /></td>
                        <td style={{ padding: '10px 16px' }}><Badge status={o.dispatch_status} type="dispatch" /></td>
                    </tr>
                ))}
            </tbody>
        </table>
    )
}

export function StatCard({ label, value, count = false, highlight = false }) {
    const display = count ? value : `₹${fmtNum(value)}`
    return (
        <div style={{ background: '#fff', borderRadius: 10, border: `1px solid ${highlight ? '#fecaca' : '#e2e8f0'}`, padding: '16px 20px' }}>
            <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.06em', marginBottom: 6 }}>{label}</div>
            <div style={{ fontSize: 22, fontWeight: 700, color: highlight ? '#dc2626' : '#0f172a' }}>{display}</div>
        </div>
    )
}

export function Badge({ status, type }) {
    const palettes = {
        payment:  { paid: ['#dcfce7','#16a34a'], partial: ['#fef9c3','#854d0e'], unpaid: ['#fee2e2','#dc2626'], overdue: ['#fee2e2','#dc2626'] },
        dispatch: { delivered: ['#dcfce7','#16a34a'], partial: ['#dbeafe','#1d4ed8'], sent: ['#f1f5f9','#475569'], pending: ['#f1f5f9','#475569'] },
    }
    const [bg, fg] = palettes[type]?.[status] ?? ['#f1f5f9', '#475569']
    return (
        <span style={{ background: bg, color: fg, fontSize: 11, fontWeight: 600, padding: '2px 8px', borderRadius: 99, display: 'inline-block' }}>
            {status}
        </span>
    )
}

export function Centered({ children }) {
    return <div style={{ height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>{children}</div>
}

export function Spinner() {
    return <div style={{ width: 20, height: 20, border: '2px solid #e2e8f0', borderTopColor: '#4f46e5', borderRadius: '50%', animation: 'spin 0.6s linear infinite' }} />
}

export function PageHeader({ title, action }) {
    return (
        <div style={{ padding: '24px 28px 0', display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 20 }}>
            <h1 style={{ fontSize: 18, fontWeight: 700, color: '#0f172a', margin: 0 }}>{title}</h1>
            {action}
        </div>
    )
}

export function fmtDate(d) {
    if (!d) return '—'
    return new Date(d).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' })
}

export function fmtNum(n) {
    return Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 })
}

// ── Icons ─────────────────────────────────────────────────────────────────────

function IconDash({ size = 16 }) {
    return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
}
function IconOrders({ size = 16 }) {
    return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
}
function IconClients({ size = 16 }) {
    return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path strokeLinecap="round" strokeLinejoin="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
}
function IconProducts({ size = 16 }) {
    return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/></svg>
}
function IconAnalytics({ size = 16 }) {
    return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
}
function IconLogout({ size = 16 }) {
    return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"><path strokeLinecap="round" strokeLinejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
}

// inject spin keyframe once
if (typeof document !== 'undefined' && !document.getElementById('spin-kf')) {
    const s = document.createElement('style')
    s.id = 'spin-kf'
    s.textContent = '@keyframes spin { to { transform: rotate(360deg); } }'
    document.head.appendChild(s)
}
