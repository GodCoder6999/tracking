import { useEffect, useState } from 'react'
import api from '../api'
import {
    LineChart, Line, BarChart, Bar, PieChart, Pie, Cell,
    XAxis, YAxis, Tooltip, ResponsiveContainer, Legend,
} from 'recharts'
import { Centered, Spinner, PageHeader, fmtNum } from './Dashboard'

const MONTHS_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']

const PIE_COLORS_PAY  = { paid: '#16a34a', partial: '#eab308', unpaid: '#ef4444', overdue: '#dc2626' }
const PIE_COLORS_DISP = { delivered: '#16a34a', sent: '#3b82f6', partial: '#a855f7', pending: '#94a3b8' }

function monthLabel(ym) {
    const [, m] = ym.split('-')
    return MONTHS_SHORT[parseInt(m, 10) - 1]
}

export default function AnalyticsPage() {
    const [data,    setData]    = useState(null)
    const [loading, setLoading] = useState(true)
    const [error,   setError]   = useState('')
    const [from,    setFrom]    = useState('')
    const [to,      setTo]      = useState('')
    const [clientId, setClientId] = useState('')
    const [sortKey,  setSortKey]  = useState('revenue')
    const [sortDir,  setSortDir]  = useState(-1)

    function load() {
        setLoading(true)
        setError('')
        api.analytics({ from: from || undefined, to: to || undefined, client_id: clientId || undefined })
            .then(setData)
            .catch(err => setError(err?.response?.data?.message || 'Failed to load analytics.'))
            .finally(() => setLoading(false))
    }

    useEffect(() => { load() }, [])

    if (loading) return <Centered><Spinner /></Centered>
    if (error)   return <Centered><div style={{ color: '#ef4444', fontSize: 13 }}>{error}</div></Centered>

    const { totals, months, monthlyLine, paymentStatus, dispatchStatus, funnel, clientStats, topProducts, clientsList } = data

    const monthlyData = months.map((m, i) => ({ month: monthLabel(m), revenue: monthlyLine[i] ?? 0 }))
    const payPie  = Object.entries(paymentStatus  || {}).map(([name, value]) => ({ name, value: Number(value) }))
    const dispPie = Object.entries(dispatchStatus || {}).map(([name, value]) => ({ name, value: Number(value) }))
    const topProdData = (topProducts || []).map(p => ({ name: p.particulars.length > 18 ? p.particulars.slice(0, 18) + '…' : p.particulars, revenue: p.revenue, units: p.units }))
    const clientBarData = (clientStats || []).filter(c => c.orders > 0).sort((a, b) => b.revenue - a.revenue).slice(0, 10)

    return (
        <div style={{ padding: 28 }}>
            <PageHeader title="Analytics" />

            {/* Filters */}
            <div style={{ display: 'flex', gap: 8, marginBottom: 20, flexWrap: 'wrap', alignItems: 'flex-end' }}>
                <div>
                    <Label>From</Label>
                    <input type="date" value={from} onChange={e => setFrom(e.target.value)} style={inp} />
                </div>
                <div>
                    <Label>To</Label>
                    <input type="date" value={to} onChange={e => setTo(e.target.value)} style={inp} />
                </div>
                {clientsList?.length > 0 && (
                    <div>
                        <Label>Client</Label>
                        <select value={clientId} onChange={e => setClientId(e.target.value)} style={inp}>
                            <option value="">All Clients</option>
                            {clientsList.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                        </select>
                    </div>
                )}
                <button onClick={load} style={filterBtn}>Apply</button>
                {(from || to || clientId) && (
                    <button onClick={() => { setFrom(''); setTo(''); setClientId('') }} style={{ ...filterBtn, background: '#f1f5f9', color: '#475569' }}>Clear</button>
                )}
            </div>

            {/* Totals */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)', gap: 12, marginBottom: 20 }}>
                <TotCard label="Revenue"    value={`₹${fmtNum(totals.total_revenue)}`} />
                <TotCard label="Received"   value={`₹${fmtNum(totals.total_received)}`} color="#16a34a" />
                <TotCard label="Due"        value={`₹${fmtNum(totals.total_due)}`}     color={totals.total_due > 0 ? '#dc2626' : '#16a34a'} />
                <TotCard label="Orders"     value={totals.total_orders}  />
                <TotCard label="Clients"    value={totals.total_clients} />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16 }}>
                {/* Monthly Revenue */}
                <ChartCard title="Monthly Revenue (12 months)">
                    <ResponsiveContainer width="100%" height={220}>
                        <LineChart data={monthlyData}>
                            <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                            <YAxis tickFormatter={v => `₹${fmtNum(v)}`} tick={{ fontSize: 11 }} width={70} />
                            <Tooltip formatter={v => [`₹${fmtNum(v)}`, 'Revenue']} />
                            <Line type="monotone" dataKey="revenue" stroke="#4f46e5" strokeWidth={2} dot={false} />
                        </LineChart>
                    </ResponsiveContainer>
                </ChartCard>

                {/* Funnel */}
                <ChartCard title="Order Pipeline">
                    <ResponsiveContainer width="100%" height={220}>
                        <BarChart data={funnel} layout="vertical">
                            <XAxis type="number" tick={{ fontSize: 11 }} />
                            <YAxis type="category" dataKey="label" tick={{ fontSize: 11 }} width={100} />
                            <Tooltip />
                            <Bar dataKey="value" fill="#4f46e5" radius={[0, 4, 4, 0]} />
                        </BarChart>
                    </ResponsiveContainer>
                </ChartCard>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16 }}>
                {/* Payment Status Pie */}
                <ChartCard title="Payment Status">
                    {payPie.length === 0
                        ? <Empty />
                        : <ResponsiveContainer width="100%" height={200}>
                            <PieChart>
                                <Pie data={payPie} cx="50%" cy="50%" outerRadius={75} dataKey="value" label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`} labelLine={false}>
                                    {payPie.map((entry, i) => <Cell key={i} fill={PIE_COLORS_PAY[entry.name] || '#94a3b8'} />)}
                                </Pie>
                                <Tooltip />
                            </PieChart>
                        </ResponsiveContainer>
                    }
                </ChartCard>

                {/* Dispatch Status Pie */}
                <ChartCard title="Dispatch Status">
                    {dispPie.length === 0
                        ? <Empty />
                        : <ResponsiveContainer width="100%" height={200}>
                            <PieChart>
                                <Pie data={dispPie} cx="50%" cy="50%" outerRadius={75} dataKey="value" label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`} labelLine={false}>
                                    {dispPie.map((entry, i) => <Cell key={i} fill={PIE_COLORS_DISP[entry.name] || '#94a3b8'} />)}
                                </Pie>
                                <Tooltip />
                            </PieChart>
                        </ResponsiveContainer>
                    }
                </ChartCard>
            </div>

            {/* Top Products */}
            {topProdData.length > 0 && (
                <ChartCard title="Top Products by Revenue" style={{ marginBottom: 16 }}>
                    <ResponsiveContainer width="100%" height={240}>
                        <BarChart data={topProdData}>
                            <XAxis dataKey="name" tick={{ fontSize: 10 }} />
                            <YAxis yAxisId="rev" tickFormatter={v => `₹${fmtNum(v)}`} tick={{ fontSize: 11 }} width={70} />
                            <YAxis yAxisId="units" orientation="right" tick={{ fontSize: 11 }} width={40} />
                            <Tooltip formatter={(v, name) => name === 'revenue' ? [`₹${fmtNum(v)}`, 'Revenue'] : [v, 'Units']} />
                            <Legend />
                            <Bar yAxisId="rev"   dataKey="revenue" fill="#4f46e5" name="revenue" radius={[4, 4, 0, 0]} />
                            <Bar yAxisId="units" dataKey="units"   fill="#a5b4fc" name="units"   radius={[4, 4, 0, 0]} />
                        </BarChart>
                    </ResponsiveContainer>
                </ChartCard>
            )}

            {/* Client Revenue Bar */}
            {clientBarData.length > 0 && (
                <ChartCard title="Revenue by Client" style={{ marginBottom: 16 }}>
                    <ResponsiveContainer width="100%" height={220}>
                        <BarChart data={clientBarData}>
                            <XAxis dataKey="name" tick={{ fontSize: 10 }} />
                            <YAxis tickFormatter={v => `₹${fmtNum(v)}`} tick={{ fontSize: 11 }} width={70} />
                            <Tooltip formatter={(v, name) => [`₹${fmtNum(v)}`, name === 'revenue' ? 'Revenue' : 'Due']} />
                            <Legend />
                            <Bar dataKey="revenue"  fill="#4f46e5" name="revenue" radius={[4, 4, 0, 0]} stackId="a" />
                            <Bar dataKey="due"      fill="#fca5a5" name="due"     radius={[4, 4, 0, 0]} />
                        </BarChart>
                    </ResponsiveContainer>
                </ChartCard>
            )}

            {/* Client Table */}
            {clientStats?.length > 0 && (
                <ChartCard title="Client Breakdown">
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                            <thead>
                                <tr style={{ borderBottom: '1px solid #f1f5f9' }}>
                                    {[
                                        { label: 'Client',       key: 'name'     },
                                        { label: 'Status',       key: 'is_active'},
                                        { label: 'Orders',       key: 'orders'   },
                                        { label: 'Revenue',      key: 'revenue'  },
                                        { label: 'Received',     key: 'received' },
                                        { label: 'Due',          key: 'due'      },
                                        { label: 'Collection %', key: 'pct'      },
                                    ].map(({ label, key }) => (
                                        <th key={key} onClick={() => {
                                            if (sortKey === key) setSortDir(d => -d)
                                            else { setSortKey(key); setSortDir(-1) }
                                        }} style={{ ...colh, cursor: 'pointer', userSelect: 'none' }}>
                                            {label}{sortKey === key ? (sortDir === -1 ? ' ↓' : ' ↑') : ''}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {[...clientStats]
                                    .map(c => ({ ...c, pct: c.revenue > 0 ? Math.round((c.received / c.revenue) * 100) : 0 }))
                                    .sort((a, b) => {
                                        const av = a[sortKey] ?? 0, bv = b[sortKey] ?? 0
                                        if (typeof av === 'string') return sortDir * av.localeCompare(bv)
                                        return sortDir * (bv - av)
                                    })
                                    .map(c => {
                                        const pctColor = c.pct >= 80 ? '#16a34a' : c.pct >= 50 ? '#d97706' : '#dc2626'
                                        return (
                                            <tr key={c.id}
                                                style={{ borderBottom: '1px solid #f8fafc' }}
                                                onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                                                onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                            >
                                                <td style={cold}>{c.name}</td>
                                                <td style={cold}>
                                                    <span style={{ fontSize: 11, fontWeight: 600, padding: '2px 7px', borderRadius: 99, background: c.is_active ? '#dcfce7' : '#f1f5f9', color: c.is_active ? '#16a34a' : '#94a3b8' }}>
                                                        {c.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td style={cold}>{c.orders}</td>
                                                <td style={{ ...cold, fontWeight: 600 }}>₹{fmtNum(c.revenue)}</td>
                                                <td style={{ ...cold, color: '#16a34a' }}>₹{fmtNum(c.received)}</td>
                                                <td style={{ ...cold, color: c.due > 0 ? '#dc2626' : '#64748b' }}>₹{fmtNum(c.due)}</td>
                                                <td style={{ ...cold, fontWeight: 700, color: pctColor }}>{c.pct}%</td>
                                            </tr>
                                        )
                                    })
                                }
                            </tbody>
                        </table>
                    </div>
                </ChartCard>
            )}
        </div>
    )
}

function TotCard({ label, value, color = '#0f172a' }) {
    return (
        <div style={{ background: '#fff', borderRadius: 10, border: '1px solid #e2e8f0', padding: '14px 18px' }}>
            <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.06em', marginBottom: 6 }}>{label}</div>
            <div style={{ fontSize: 20, fontWeight: 700, color }}>{value}</div>
        </div>
    )
}

function ChartCard({ title, children, style }) {
    return (
        <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e2e8f0', overflow: 'hidden', ...style }}>
            <div style={{ padding: '11px 16px', borderBottom: '1px solid #f1f5f9', fontWeight: 600, fontSize: 13, color: '#0f172a' }}>{title}</div>
            <div style={{ padding: 16 }}>{children}</div>
        </div>
    )
}

function Empty() {
    return <div style={{ padding: 40, textAlign: 'center', color: '#94a3b8', fontSize: 13 }}>No data yet.</div>
}

function Label({ children }) {
    return <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 4, letterSpacing: '0.05em' }}>{children}</div>
}

const inp       = { padding: '7px 10px', fontSize: 13, border: '1px solid #e2e8f0', borderRadius: 7, outline: 'none', background: '#fff', color: '#334155' }
const filterBtn = { padding: '7px 16px', fontSize: 13, fontWeight: 600, borderRadius: 7, border: 'none', background: '#4f46e5', color: '#fff', cursor: 'pointer', alignSelf: 'flex-end' }
const colh      = { padding: '8px 16px', textAlign: 'left', color: '#94a3b8', fontWeight: 600, fontSize: 11, textTransform: 'uppercase', letterSpacing: '0.04em' }
const cold      = { padding: '10px 16px', color: '#334155' }
