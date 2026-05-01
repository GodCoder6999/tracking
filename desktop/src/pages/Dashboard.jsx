import { useEffect, useState } from 'react'
import api from '../api'

function StatCard({ label, value, prefix = '₹', isCount = false }) {
    const display = isCount ? value : `${prefix}${Number(value).toLocaleString('en-IN', { maximumFractionDigits: 0 })}`
    return (
        <div className="bg-white rounded-xl border border-slate-100 p-5 shadow-sm">
            <div className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">{label}</div>
            <div className="text-2xl font-bold text-slate-900">{isCount ? value : display}</div>
        </div>
    )
}

export default function Dashboard({ dealer, onLogout }) {
    const [data, setData]       = useState(null)
    const [loading, setLoading] = useState(true)
    const [error, setError]     = useState('')

    useEffect(() => {
        api.dashboard()
            .then(setData)
            .catch(err => {
                const status = err?.response?.status
                const msg    = err?.response?.data?.message || err?.message || 'unknown'
                setError(`Failed to load dashboard. [${status ?? 'network'}] ${msg}`)
            })
            .finally(() => setLoading(false))
    }, [])

    if (loading) return (
        <div className="min-h-screen bg-slate-50 flex items-center justify-center">
            <div className="text-slate-400 text-sm">Loading…</div>
        </div>
    )

    if (error) return (
        <div className="min-h-screen bg-slate-50 flex items-center justify-center">
            <div className="text-red-500 text-sm">{error}</div>
        </div>
    )

    const { stats, recent } = data

    return (
        <div className="min-h-screen bg-slate-50">
            <header className="bg-white border-b border-slate-100 px-8 py-4 flex items-center justify-between sticky top-0 z-10">
                <div className="flex items-center gap-3">
                    <div className="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-extrabold text-sm">T</div>
                    <span className="font-bold text-slate-900">Track</span>
                </div>
                <div className="flex items-center gap-4">
                    <span className="text-sm text-slate-500">{dealer.name}</span>
                    <button
                        onClick={onLogout}
                        className="text-xs text-slate-400 hover:text-slate-700 transition-colors"
                    >
                        Sign out
                    </button>
                </div>
            </header>

            <main className="p-8 space-y-6 max-w-6xl mx-auto">
                <h1 className="text-lg font-bold text-slate-900">Dashboard</h1>

                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <StatCard label="Clients"          value={stats.clients}          isCount />
                    <StatCard label="Orders"           value={stats.orders}           isCount />
                    <StatCard label="Revenue"          value={stats.revenue} />
                    <StatCard label="Received"         value={stats.received} />
                    <StatCard label="Due"              value={stats.due} />
                    <StatCard label="Pending Dispatch" value={stats.pending_dispatch} isCount />
                </div>

                <div className="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-slate-100">
                        <h2 className="font-semibold text-slate-900">Recent Orders</h2>
                    </div>
                    {recent.length === 0 ? (
                        <div className="p-8 text-center text-slate-400 text-sm">No orders yet.</div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-xs text-slate-400 uppercase tracking-wide border-b border-slate-100">
                                    <th className="px-6 py-3 text-left font-semibold">Order #</th>
                                    <th className="px-6 py-3 text-left font-semibold">Client</th>
                                    <th className="px-6 py-3 text-left font-semibold">Date</th>
                                    <th className="px-6 py-3 text-right font-semibold">Amount</th>
                                    <th className="px-6 py-3 text-left font-semibold">Payment</th>
                                    <th className="px-6 py-3 text-left font-semibold">Dispatch</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recent.map(order => (
                                    <tr key={order.id} className="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                        <td className="px-6 py-3 font-mono font-semibold text-indigo-700">{order.order_number}</td>
                                        <td className="px-6 py-3 text-slate-700">{order.client?.name}</td>
                                        <td className="px-6 py-3 text-slate-500">{order.order_date}</td>
                                        <td className="px-6 py-3 text-right font-semibold text-slate-900">
                                            ₹{Number(order.total_amount).toLocaleString('en-IN', { maximumFractionDigits: 0 })}
                                        </td>
                                        <td className="px-6 py-3">
                                            <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${
                                                order.payment_status === 'paid'    ? 'bg-emerald-100 text-emerald-700' :
                                                order.payment_status === 'partial' ? 'bg-amber-100 text-amber-700' :
                                                'bg-red-100 text-red-700'
                                            }`}>{order.payment_status}</span>
                                        </td>
                                        <td className="px-6 py-3">
                                            <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${
                                                order.dispatch_status === 'delivered' ? 'bg-emerald-100 text-emerald-700' :
                                                order.dispatch_status === 'partial'   ? 'bg-blue-100 text-blue-700' :
                                                'bg-slate-100 text-slate-600'
                                            }`}>{order.dispatch_status}</span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </main>
        </div>
    )
}
