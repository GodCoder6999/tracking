import { useEffect, useState } from 'react'
import api from '../api'
import { Centered, Spinner, PageHeader, fmtNum } from './Dashboard'

export default function ProductsPage() {
    const [products, setProducts] = useState([])
    const [loading,  setLoading]  = useState(true)
    const [search,   setSearch]   = useState('')

    useEffect(() => {
        api.products()
            .then(setProducts)
            .finally(() => setLoading(false))
    }, [])

    const filtered = products.filter(p =>
        p.name.toLowerCase().includes(search.toLowerCase())
    )

    return (
        <div style={{ padding: 28 }}>
            <PageHeader title={`Products (${products.length})`} />

            <input
                placeholder="Search products…"
                value={search}
                onChange={e => setSearch(e.target.value)}
                style={{ padding: '8px 14px', fontSize: 13, border: '1px solid #e2e8f0', borderRadius: 8, marginBottom: 16, width: 280, outline: 'none' }}
            />

            <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e2e8f0', overflow: 'hidden' }}>
                {loading
                    ? <Centered><Spinner /></Centered>
                    : filtered.length === 0
                        ? <div style={{ padding: 40, textAlign: 'center', color: '#94a3b8', fontSize: 13 }}>No products found.</div>
                        : (
                            <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                                <thead>
                                    <tr style={{ borderBottom: '1px solid #f1f5f9' }}>
                                        {['', 'Product Name', 'Rate', 'Dealer Cost', 'Stock'].map(h => (
                                            <th key={h} style={{ padding: '9px 16px', textAlign: 'left', color: '#94a3b8', fontWeight: 600, fontSize: 11, textTransform: 'uppercase' }}>{h}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {filtered.map(p => (
                                        <tr key={p.id}
                                            style={{ borderBottom: '1px solid #f8fafc' }}
                                            onMouseEnter={e => e.currentTarget.style.background = '#f8fafc'}
                                            onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                                        >
                                            <td style={{ padding: '8px 12px', width: 56 }}>
                                                <img
                                                    src={`https://picsum.photos/seed/${encodeURIComponent((p.name || '').toLowerCase().trim())}/48/48`}
                                                    alt=""
                                                    style={{ width: 44, height: 44, objectFit: 'cover', borderRadius: 6, border: '1px solid #e2e8f0' }}
                                                    onError={e => { e.target.src = 'https://picsum.photos/seed/default/48/48' }}
                                                />
                                            </td>
                                            <td style={{ padding: '11px 16px', fontWeight: 500, color: '#0f172a' }}>
                                                {p.name}
                                                {p.description && <div style={{ fontSize: 11, color: '#94a3b8', fontWeight: 400, marginTop: 2 }}>{p.description}</div>}
                                            </td>
                                            <td style={{ padding: '11px 16px', color: '#334155', fontWeight: 600 }}>₹{fmtNum(p.rate)}</td>
                                            <td style={{ padding: '11px 16px', color: '#64748b' }}>{p.dealer_cost ? `₹${fmtNum(p.dealer_cost)}` : '—'}</td>
                                            <td style={{ padding: '11px 16px', color: p.stock > 0 ? '#16a34a' : '#94a3b8' }}>{p.stock ?? '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )
                }
            </div>
        </div>
    )
}
