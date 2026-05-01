import { useEffect, useState } from 'react'
import api from '../api'
import { Centered, Spinner, fmtNum } from './Dashboard'

const PAYMENT_METHODS = ['UPI','Cash','Bank Transfer','Cheque','NEFT','RTGS','IMPS','DD']
const SHIPPING_METHODS = ['Courier','Hand Delivery','Speed Post','Air Cargo','Surface Cargo','By Hand']
const INVOICING_TERMS  = ['Immediate','Net 7','Net 15','Net 30','Net 45','Net 60','COD']

const emptyItem = () => ({ product_id: '', particulars: '', qty: 1, rate: '', dealer_cost: '', discount_percent: 0, gst_rate: 0 })

function calcItem(it) {
    const qty  = parseFloat(it.qty)  || 0
    const rate = parseFloat(it.rate) || 0
    const disc = parseFloat(it.discount_percent) || 0
    const gst  = parseFloat(it.gst_rate)  || 0
    const sub  = qty * rate
    const disc_amt = sub * disc / 100
    const after_disc = sub - disc_amt
    const gst_amt = after_disc * gst / 100
    return {
        subtotal:   sub,
        disc_amt:   disc_amt,
        gst_amt:    gst_amt,
        amount:     after_disc + gst_amt,
    }
}

export default function CreateOrder({ onBack, onCreated }) {
    const [clients,  setClients]  = useState([])
    const [products, setProducts] = useState([])
    const [loading,  setLoading]  = useState(true)
    const [saving,   setSaving]   = useState(false)

    const today = new Date().toISOString().slice(0, 10)
    const [form, setForm] = useState({
        client_id: '', order_date: today,
        shipping_method: '', requested_delivery_date: '',
        payment_method: '', invoicing_terms: '',
        token_amount: '', shipping_address: '', billing_address: '',
        notes: '', internal_notes: '',
    })
    const [items, setItems] = useState([emptyItem()])

    useEffect(() => {
        Promise.all([api.clients(), api.products()])
            .then(([c, p]) => { setClients(c); setProducts(p) })
            .finally(() => setLoading(false))
    }, [])

    const setF = (k, v) => setForm(f => ({ ...f, [k]: v }))

    function setItem(idx, k, v) {
        setItems(prev => {
            const next = [...prev]
            next[idx] = { ...next[idx], [k]: v }
            if (k === 'product_id') {
                const prod = products.find(p => String(p.id) === String(v))
                if (prod) {
                    next[idx].particulars  = prod.name
                    next[idx].rate         = String(prod.rate || '')
                    next[idx].dealer_cost  = String(prod.dealer_cost || '')
                }
            }
            return next
        })
    }

    function addItem()      { setItems(prev => [...prev, emptyItem()]) }
    function removeItem(i)  { setItems(prev => prev.filter((_, idx) => idx !== i)) }

    const calcs = items.map(calcItem)
    const subtotal   = calcs.reduce((s, c) => s + c.subtotal,  0)
    const totalDisc  = calcs.reduce((s, c) => s + c.disc_amt,  0)
    const totalGst   = calcs.reduce((s, c) => s + c.gst_amt,   0)
    const grandTotal = calcs.reduce((s, c) => s + c.amount,    0)
    const totalCost  = items.reduce((s, it) => {
        const c = parseFloat(it.dealer_cost) || 0
        const q = parseFloat(it.qty) || 0
        return s + c * q
    }, 0)
    const margin = grandTotal - totalCost

    async function submit(e) {
        e.preventDefault()
        if (!form.client_id) { alert('Select a client.'); return }
        const payload = {
            ...form,
            token_amount: form.token_amount ? parseFloat(form.token_amount) : 0,
            items: items.map(it => ({
                product_id:       it.product_id || null,
                particulars:      it.particulars,
                qty:              parseInt(it.qty),
                rate:             parseFloat(it.rate),
                dealer_cost:      it.dealer_cost ? parseFloat(it.dealer_cost) : null,
                discount_percent: parseFloat(it.discount_percent) || 0,
                gst_rate:         parseFloat(it.gst_rate) || 0,
            })),
        }
        setSaving(true)
        try {
            await api.orderCreate(payload)
            onCreated()
        } catch (err) {
            const errs = err?.response?.data?.errors
            alert(errs ? Object.values(errs).flat().join('\n') : (err?.response?.data?.message || 'Failed to create order.'))
        } finally {
            setSaving(false)
        }
    }

    if (loading) return <Centered><Spinner /></Centered>

    return (
        <div style={{ padding: 28, maxWidth: 900 }}>
            <button onClick={onBack} style={backBtn}>← Back to Orders</button>
            <h1 style={{ fontSize: 18, fontWeight: 700, color: '#0f172a', marginBottom: 24 }}>New Order</h1>

            <form onSubmit={submit}>
                {/* ── Order Info ── */}
                <Card title="Order Info">
                    <div style={grid2}>
                        <Field label="Client *">
                            <select required value={form.client_id} onChange={e => setF('client_id', e.target.value)} style={sel}>
                                <option value="">Select client…</option>
                                {clients.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                            </select>
                        </Field>
                        <Field label="Order Date *">
                            <input type="date" required value={form.order_date} onChange={e => setF('order_date', e.target.value)} style={inp} />
                        </Field>
                        <Field label="Shipping Method">
                            <select value={form.shipping_method} onChange={e => setF('shipping_method', e.target.value)} style={sel}>
                                <option value="">—</option>
                                {SHIPPING_METHODS.map(m => <option key={m}>{m}</option>)}
                            </select>
                        </Field>
                        <Field label="Expected Delivery">
                            <input type="date" value={form.requested_delivery_date} onChange={e => setF('requested_delivery_date', e.target.value)} style={inp} />
                        </Field>
                        <Field label="Payment Method">
                            <select value={form.payment_method} onChange={e => setF('payment_method', e.target.value)} style={sel}>
                                <option value="">—</option>
                                {PAYMENT_METHODS.map(m => <option key={m}>{m}</option>)}
                            </select>
                        </Field>
                        <Field label="Invoicing Terms">
                            <select value={form.invoicing_terms} onChange={e => setF('invoicing_terms', e.target.value)} style={sel}>
                                <option value="">—</option>
                                {INVOICING_TERMS.map(t => <option key={t}>{t}</option>)}
                            </select>
                        </Field>
                        <Field label="Token Amount ₹">
                            <input type="number" min="0" step="0.01" value={form.token_amount} onChange={e => setF('token_amount', e.target.value)} style={inp} />
                        </Field>
                    </div>
                    <div style={{ padding: '0 16px 16px', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                        <Field label="Shipping Address">
                            <textarea rows={2} value={form.shipping_address} onChange={e => setF('shipping_address', e.target.value)} style={{ ...inp, width: '100%', resize: 'vertical', minHeight: 58 }} />
                        </Field>
                        <Field label="Billing Address">
                            <textarea rows={2} value={form.billing_address} onChange={e => setF('billing_address', e.target.value)} style={{ ...inp, width: '100%', resize: 'vertical', minHeight: 58 }} />
                        </Field>
                    </div>
                </Card>

                {/* ── Items ── */}
                <Card title="Items">
                    <div style={{ overflowX: 'auto' }}>
                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                            <thead>
                                <tr style={{ borderBottom: '1px solid #f1f5f9' }}>
                                    {['Product', 'Particulars *', 'Qty *', 'Rate ₹ *', 'Dealer Cost', 'Disc %', 'GST %', 'Amount', ''].map(h => (
                                        <th key={h} style={th}>{h}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {items.map((it, i) => {
                                    const c = calcs[i]
                                    return (
                                        <tr key={i} style={{ borderBottom: '1px solid #f8fafc' }}>
                                            <td style={td}>
                                                <select value={it.product_id} onChange={e => setItem(i, 'product_id', e.target.value)} style={{ ...inp, width: 130 }}>
                                                    <option value="">Custom</option>
                                                    {products.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                                                </select>
                                            </td>
                                            <td style={td}>
                                                <input required value={it.particulars} onChange={e => setItem(i, 'particulars', e.target.value)} placeholder="Description" style={{ ...inp, width: 150 }} />
                                            </td>
                                            <td style={td}>
                                                <input type="number" required min="1" value={it.qty} onChange={e => setItem(i, 'qty', e.target.value)} style={{ ...inp, width: 60 }} />
                                            </td>
                                            <td style={td}>
                                                <input type="number" required min="0" step="0.01" value={it.rate} onChange={e => setItem(i, 'rate', e.target.value)} style={{ ...inp, width: 90 }} />
                                            </td>
                                            <td style={td}>
                                                <input type="number" min="0" step="0.01" value={it.dealer_cost} onChange={e => setItem(i, 'dealer_cost', e.target.value)} style={{ ...inp, width: 90 }} />
                                            </td>
                                            <td style={td}>
                                                <input type="number" min="0" max="100" step="0.1" value={it.discount_percent} onChange={e => setItem(i, 'discount_percent', e.target.value)} style={{ ...inp, width: 60 }} />
                                            </td>
                                            <td style={td}>
                                                <input type="number" min="0" max="100" step="0.1" value={it.gst_rate} onChange={e => setItem(i, 'gst_rate', e.target.value)} style={{ ...inp, width: 60 }} />
                                            </td>
                                            <td style={{ ...td, fontWeight: 600, color: '#0f172a', whiteSpace: 'nowrap' }}>
                                                ₹{fmtNum(c.amount)}
                                            </td>
                                            <td style={td}>
                                                {items.length > 1 && (
                                                    <button type="button" onClick={() => removeItem(i)} style={{ background: 'none', border: 'none', color: '#ef4444', cursor: 'pointer', fontSize: 16, lineHeight: 1 }}>×</button>
                                                )}
                                            </td>
                                        </tr>
                                    )
                                })}
                            </tbody>
                        </table>
                    </div>
                    <div style={{ padding: '10px 16px', borderTop: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <button type="button" onClick={addItem} style={{ fontSize: 13, color: '#4f46e5', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 500 }}>+ Add Item</button>
                        <div style={{ fontSize: 13, color: '#334155', display: 'flex', gap: 24 }}>
                            {totalDisc > 0 && <span>Discount: <strong>−₹{fmtNum(totalDisc)}</strong></span>}
                            {totalGst  > 0 && <span>GST: <strong>+₹{fmtNum(totalGst)}</strong></span>}
                            {totalCost > 0 && <span style={{ color: '#64748b' }}>Margin: <strong style={{ color: margin >= 0 ? '#16a34a' : '#dc2626' }}>₹{fmtNum(margin)}</strong></span>}
                            <span style={{ fontWeight: 700, color: '#0f172a', fontSize: 15 }}>Total: ₹{fmtNum(grandTotal)}</span>
                        </div>
                    </div>
                </Card>

                {/* ── Notes ── */}
                <Card title="Notes">
                    <div style={{ padding: '0 16px 16px', display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                        <Field label="Client Notes">
                            <textarea rows={3} value={form.notes} onChange={e => setF('notes', e.target.value)} placeholder="Visible to client…" style={{ ...inp, width: '100%', resize: 'vertical' }} />
                        </Field>
                        <Field label="Internal Notes">
                            <textarea rows={3} value={form.internal_notes} onChange={e => setF('internal_notes', e.target.value)} placeholder="Internal only…" style={{ ...inp, width: '100%', resize: 'vertical', background: '#fffbeb' }} />
                        </Field>
                    </div>
                </Card>

                <div style={{ display: 'flex', gap: 12, marginTop: 8 }}>
                    <button type="submit" disabled={saving} style={submitBtn}>
                        {saving ? 'Creating…' : 'Create Order'}
                    </button>
                    <button type="button" onClick={onBack} style={cancelBtn}>Cancel</button>
                </div>
            </form>
        </div>
    )
}

function Card({ title, children }) {
    return (
        <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e2e8f0', marginBottom: 16, overflow: 'hidden' }}>
            <div style={{ padding: '11px 16px', borderBottom: '1px solid #f1f5f9', fontWeight: 600, fontSize: 13, color: '#0f172a' }}>{title}</div>
            {children}
        </div>
    )
}

function Field({ label, children }) {
    return (
        <div style={{ padding: '0 0 0 0' }}>
            <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', letterSpacing: '0.05em', marginBottom: 4 }}>{label}</div>
            {children}
        </div>
    )
}

const backBtn   = { background: 'none', border: 'none', color: '#4f46e5', cursor: 'pointer', fontSize: 13, marginBottom: 16, fontWeight: 500, padding: 0 }
const submitBtn = { padding: '10px 24px', fontSize: 14, fontWeight: 600, borderRadius: 8, border: 'none', background: '#4f46e5', color: '#fff', cursor: 'pointer' }
const cancelBtn = { padding: '10px 24px', fontSize: 14, fontWeight: 500, borderRadius: 8, border: '1px solid #e2e8f0', background: '#fff', color: '#475569', cursor: 'pointer' }
const inp  = { padding: '7px 10px', fontSize: 13, border: '1px solid #e2e8f0', borderRadius: 7, outline: 'none', background: '#fff', color: '#334155' }
const sel  = { ...inp }
const th   = { padding: '8px 10px', textAlign: 'left', color: '#94a3b8', fontWeight: 600, fontSize: 11, textTransform: 'uppercase', letterSpacing: '0.04em', whiteSpace: 'nowrap' }
const td   = { padding: '8px 10px', verticalAlign: 'middle' }
const grid2 = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12, padding: '12px 16px 8px' }
