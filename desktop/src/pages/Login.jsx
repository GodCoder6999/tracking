import { useState } from 'react'
import api from '../api'

export default function Login({ deviceInfo, onLogin }) {
    const [email, setEmail]       = useState('')
    const [password, setPassword] = useState('')
    const [error, setError]       = useState('')
    const [loading, setLoading]   = useState(false)

    async function handleSubmit(e) {
        e.preventDefault()
        setError('')
        setLoading(true)

        try {
            const res = await api.login(email, password, deviceInfo)

            if (res.status === 'pending' || res.status === 'rejected') {
                onLogin({ deviceStatus: res.status })
                return
            }

            onLogin({ token: res.token, dealer: res.dealer })
        } catch (err) {
            const status = err?.response?.status ?? 'network'
            const msg    = err?.response?.data?.error || err?.response?.data?.message || err?.message || '?'
            setError(`[${status}] ${msg}`)
        } finally {
            setLoading(false)
        }
    }

    return (
        <div className="min-h-screen bg-slate-900 flex items-center justify-center p-6">
            <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">

                <div className="px-8 pt-8 pb-6 text-center bg-gradient-to-b from-indigo-50 to-white border-b border-slate-100">
                    <div className="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <span className="text-white font-extrabold text-xl">T</span>
                    </div>
                    <h1 className="text-xl font-bold text-slate-900">Track</h1>
                    <p className="text-slate-500 text-sm mt-1">Sign in to your dealer account</p>
                </div>

                <form onSubmit={handleSubmit} className="px-8 py-7 space-y-5">
                    {error && (
                        <div className="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                            {error}
                        </div>
                    )}

                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Email</label>
                        <input
                            type="email"
                            value={email}
                            onChange={e => setEmail(e.target.value)}
                            required
                            className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="you@example.com"
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-600 mb-1.5">Password</label>
                        <input
                            type="password"
                            value={password}
                            onChange={e => setPassword(e.target.value)}
                            required
                            className="w-full border border-slate-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="••••••••"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-semibold rounded-xl transition-colors text-sm"
                    >
                        {loading ? 'Signing in…' : 'Sign In'}
                    </button>
                </form>

                <div className="px-8 pb-6 text-center">
                    <p className="text-xs text-slate-400">
                        Device: {deviceInfo?.device_name || '—'}
                    </p>
                </div>
            </div>
        </div>
    )
}
