import { useEffect, useRef, useState } from 'react'
import api, { storageUrl } from '../api'
import { OrderTable, Badge, Centered, Spinner, PageHeader, fmtDate, fmtNum } from './Dashboard'
import CreateOrder from './CreateOrder'

export default function OrdersPage({ dealer, initialOrderId, onClearInitial }) {
    const [view,    setView]    = useState('list') // list | create | detail
    const [orders,  setOrders]  = useState([])
    const [meta,    setMeta]    = useState(null)
    const [loading, setLoading] = useState(true)
    const [params,  setParams]  = useState({ page: 1 })
    const [selected, setSelected] = useState(null)

    function load(p) {
        setLoading(true)
        api.orders(p)
            .then(res => { setOrders(res.data); setMeta(res) })
            .finally(() => setLoading(false))
    }

    useEffect(() => { load(params) }, [params])

    // open detail when navigated from dashboard
    useEffect(() => {
        if (initialOrderId) {
            setSelected({ id: initialOrderId })
            setView('detail')
            onClearInitial?.()
        }
    }, [initialOrderId])

    if (view === 'create') return (
        <CreateOrder
            onBack={() => setView('list')}
            onCreated={() => { setView('list'); load(params) }}
        />
    )

    if (view === 'detail' && selected) return (
        <OrderDetail
            orderId={selected.id}
            onBack={() => { setView('list'); load(params) }}
        />
    )

    return (
        <div style={{ padding: 28 }}>
            <PageHeader
                title="Orders"
                action={
                    <button onClick={() => setView('create')} style={btnStyle}>
                        + New Order
                    </button>
                }
            />

            <Filters params={params} onChange={p => setParams({ ...p, page: 1 })} />

            <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e2e8f0', overflow: 'hidden' }}>
                {loading
                    ? <Centered><Spinner /></Centered>
                    : orders.length === 0
                        ? <div style={{ padding: 40, textAlign: 'center', color: '#94a3b8', fontSize: 13 }}>No orders found.</div>
                        : <OrderTable orders={orders} onSelect={o => { setSelected(o); setView('detail') }} />
                }
            </div>

            {meta && meta.last_page > 1 && (
                <div style={{ display: 'flex', justifyContent: 'center', gap: 8, marginTop: 16 }}>
                    <PgBtn disabled={meta.current_page <= 1}           onClick={() => setParams(p => ({ ...p, page: p.page - 1 }))}>← Prev</PgBtn>
                    <span style={{ fontSize: 12, color: '#64748b', padding: '6px 12px' }}>Page {meta.current_page} of {meta.last_page}</span>
                    <PgBtn disabled={meta.current_page >= meta.last_page} onClick={() => setParams(p => ({ ...p, page: p.page + 1 }))}>Next →</PgBtn>
                </div>
            )}
        </div>
    )
}

// ── Filters ───────────────────────────────────────────────────────────────────

function Filters({ params, onChange }) {
    const [q, setQ] = useState(params.q || '')
    const active = params.q || params.payment_status || params.dispatch_status || params.from || params.to

    return (
        <div style={{ display: 'flex', gap: 8, marginBottom: 16, flexWrap: 'wrap', alignItems: 'center' }}>
            <input placeholder="Search order #" value={q} onChange={e => setQ(e.target.value)}
                onKeyDown={e => e.key === 'Enter' && onChange({ ...params, q })} style={iStyle} />
            <select value={params.payment_status || ''} onChange={e => onChange({ ...params, payment_status: e.target.value })} style={iStyle}>
                <option value="">All Payment</option>
                <option value="paid">Paid</option>
                <option value="partial">Partial</option>
                <option value="unpaid">Unpaid</option>
            </select>
            <select value={params.dispatch_status || ''} onChange={e => onChange({ ...params, dispatch_status: e.target.value })} style={iStyle}>
                <option value="">All Dispatch</option>
                <option value="pending">Pending</option>
                <option value="sent">Sent</option>
                <option value="partial">Partial</option>
                <option value="delivered">Delivered</option>
            </select>
            <input type="date" value={params.from || ''} onChange={e => onChange({ ...params, from: e.target.value })} style={iStyle} />
            <input type="date" value={params.to   || ''} onChange={e => onChange({ ...params, to:   e.target.value })} style={iStyle} />
            {active && <button onClick={() => { setQ(''); onChange({ page: 1 }) }} style={{ ...iStyle, cursor: 'pointer', color: '#ef4444' }}>✕ Clear</button>}
        </div>
    )
}

// ── Order Detail ──────────────────────────────────────────────────────────────

function OrderDetail({ orderId, onBack }) {
    const [order,   setOrder]   = useState(null)
    const [loading, setLoading] = useState(true)
    const [saving,  setSaving]  = useState('')

    function reload() {
        setLoading(true)
        api.orderShow(orderId).then(setOrder).finally(() => setLoading(false))
    }

    useEffect(() => { reload() }, [orderId])

    async function addPayment(data) {
        setSaving('payment')
        try { await api.paymentAdd(orderId, data); reload() }
        catch (e) { alert(validationMsg(e)) }
        finally { setSaving('') }
    }

    async function deletePayment(pid) {
        if (!confirm('Delete this payment?')) return
        setSaving('payment')
        try { await api.paymentDelete(orderId, pid); reload() }
        finally { setSaving('') }
    }

    async function addDispatch(data) {
        setSaving('dispatch')
        try { await api.dispatchAdd(orderId, data); reload() }
        catch (e) { alert(validationMsg(e)) }
        finally { setSaving('') }
    }

    async function markDelivered() {
        if (!confirm('Mark entire order as delivered?')) return
        setSaving('delivered')
        try { await api.dispatchMarkDelivered(orderId); reload() }
        finally { setSaving('') }
    }

    async function updateLabel(label_status) {
        setSaving('label')
        try { await api.orderUpdateLabel(orderId, label_status); reload() }
        finally { setSaving('') }
    }

    if (loading) return <Centered><Spinner /></Centered>
    if (!order)  return <Centered><div style={{ color: '#ef4444' }}>Failed to load.</div></Centered>

    const totalDispatched = order.dispatches?.reduce((s, d) => s + d.dispatch_qty, 0) ?? 0
    const totalQty        = order.items?.reduce((s, i) => s + i.qty, 0) ?? 0
    const remaining       = Math.max(0, totalQty - totalDispatched)

    return (
        <div style={{ padding: 28 }}>
            <button onClick={onBack} style={{ background: 'none', border: 'none', color: '#4f46e5', cursor: 'pointer', fontSize: 13, marginBottom: 16, fontWeight: 500 }}>← Back to Orders</button>

            {/* Header */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 20 }}>
                <div>
                    <h1 style={{ fontSize: 18, fontWeight: 700, color: '#0f172a', margin: 0 }}>{order.order_number}</h1>
                    <div style={{ color: '#64748b', fontSize: 13, marginTop: 4 }}>{fmtDate(order.order_date)} · {order.client?.name}</div>
                </div>
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
                    <Badge status={order.payment_status}  type="payment" />
                    <Badge status={order.dispatch_status} type="dispatch" />
                </div>
            </div>

            {/* Stats — row 1: financial */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 12, marginBottom: 12 }}>
                <StatTile label="Total"       value={`₹${fmtNum(order.total_amount)}`} />
                <StatTile label="Received"    value={`₹${fmtNum(order.total_received)}`} />
                <StatTile label="Payment Due" value={`₹${fmtNum(order.due_amount)}`} warn={order.due_amount > 0} />
                <StatTile label="Token Paid"  value={`₹${fmtNum(order.token_amount)}`} />
            </div>
            {/* Stats — row 2: tax */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 12, marginBottom: 12 }}>
                <StatTile label="Taxable Value" value={`₹${fmtNum((order.total_amount || 0) - (order.gst_amount || 0))}`} />
                <StatTile label="GST"           value={`₹${fmtNum(order.gst_amount)}`} />
                {order.discount_amount > 0 && <StatTile label="Discount Given" value={`₹${fmtNum(order.discount_amount)}`} />}
            </div>
            {/* Stats — row 3: units */}
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 12, marginBottom: 20 }}>
                <StatTile label="Units Ordered"    value={`${totalQty} units`} />
                <StatTile label="Units Dispatched" value={`${totalDispatched} units`} />
                <StatTile label="Units Remaining"  value={`${remaining} units`} accent={remaining > 0 ? '#d97706' : '#4f46e5'} warn={remaining > 0} />
            </div>

            {/* Info card */}
            <Section title="Order Info">
                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 0 }}>
                    <InfoRow label="Client"   value={order.client?.name} />
                    <InfoRow label="Date"     value={fmtDate(order.order_date)} />
                    {order.payment_method           && <InfoRow label="Payment Method"     value={order.payment_method} />}
                    {order.invoicing_terms          && <InfoRow label="Invoicing Terms"    value={order.invoicing_terms} />}
                    {order.shipping_method          && <InfoRow label="Shipping"           value={order.shipping_method} />}
                    {order.requested_delivery_date  && <InfoRow label="Expected Delivery"  value={fmtDate(order.requested_delivery_date)} />}
                    {order.full_amount_date         && <InfoRow label="Full Payment Date"  value={fmtDate(order.full_amount_date)} />}
                    {order.shipping_address         && <InfoRow label="Ship To"            value={order.shipping_address} />}
                    {order.billing_address          && <InfoRow label="Bill To"            value={order.billing_address} />}
                    {order.notes                    && <InfoRow label="Client Notes"       value={order.notes} />}
                    {order.internal_notes           && <InfoRow label="Internal Notes"     value={order.internal_notes} highlight />}
                    {order.token_proof_path         && <InfoRow label="Token Proof"        value={<a href={order.token_proof_path} target="_blank" rel="noreferrer" style={{ color: '#4f46e5', fontSize: 12 }}>View file</a>} />}
                    {order.attachment_path          && <InfoRow label="Attachment"         value={<a href={order.attachment_path}  target="_blank" rel="noreferrer" style={{ color: '#4f46e5', fontSize: 12 }}>View file</a>} />}
                </div>
                <div style={{ padding: '8px 16px', borderTop: '1px solid #f1f5f9', display: 'flex', alignItems: 'center', gap: 12 }}>
                    <span style={{ fontSize: 12, color: '#64748b' }}>Label Status:</span>
                    <select
                        value={order.label_status || 'pending'}
                        onChange={e => updateLabel(e.target.value)}
                        disabled={saving === 'label'}
                        style={{ fontSize: 12, border: '1px solid #e2e8f0', borderRadius: 5, padding: '3px 8px', color: '#334155' }}
                    >
                        <option value="pending">Pending</option>
                        <option value="printed">Printed</option>
                        <option value="attached">Attached</option>
                    </select>
                </div>
            </Section>

            {/* Items */}
            <Section title="Items">
                <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13 }}>
                    <thead>
                        <tr style={{ borderBottom: '1px solid #f1f5f9' }}>
                            {['', 'Product / Particulars', 'Qty', 'Rate', 'Cost', 'Disc%', 'GST%', 'Amount'].map(h => (
                                <th key={h} style={thStyle}>{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {order.items?.map((item, i) => (
                            <tr key={i} style={{ borderBottom: '1px solid #f8fafc' }}>
                                <td style={{ padding: '6px 10px', width: 44 }}>
                                    <img
                                        src={`https://picsum.photos/seed/${encodeURIComponent((item.particulars || '').toLowerCase().trim())}/40/40`}
                                        alt=""
                                        style={{ width: 36, height: 36, objectFit: 'cover', borderRadius: 4, border: '1px solid #e2e8f0' }}
                                        onError={e => { e.target.src = 'https://picsum.photos/seed/default/40/40' }}
                                    />
                                </td>
                                <td style={tdStyle}>{item.particulars}</td>
                                <td style={tdStyle}>{item.qty}</td>
                                <td style={tdStyle}>₹{fmtNum(item.rate)}</td>
                                <td style={{ ...tdStyle, color: '#64748b' }}>{item.dealer_cost ? `₹${fmtNum(item.dealer_cost)}` : '—'}</td>
                                <td style={tdStyle}>{item.discount_percent ? `${item.discount_percent}%` : '—'}</td>
                                <td style={tdStyle}>{item.gst_rate ? `${item.gst_rate}%` : '—'}</td>
                                <td style={{ ...tdStyle, fontWeight: 600 }}>₹{fmtNum(item.amount)}</td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot>
                        <tr style={{ borderTop: '2px solid #e2e8f0' }}>
                            <td colSpan={6} style={{ padding: '10px 14px', fontWeight: 700, color: '#0f172a', textAlign: 'right' }}>Total</td>
                            <td style={{ padding: '10px 14px', fontWeight: 700, color: '#0f172a', fontSize: 15 }}>₹{fmtNum(order.total_amount)}</td>
                        </tr>
                    </tfoot>
                </table>
            </Section>

            {/* Payments */}
            <Section title={`Payments (₹${fmtNum(order.total_received)} received)`}>
                {order.payments?.length > 0 && (
                    <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13, marginBottom: 8 }}>
                        <thead>
                            <tr style={{ borderBottom: '1px solid #f1f5f9' }}>
                                {['Date', 'Amount', 'Method', 'Note', ''].map(h => <th key={h} style={thStyle}>{h}</th>)}
                            </tr>
                        </thead>
                        <tbody>
                            {order.payments.map((p) => (
                                <tr key={p.id} style={{ borderBottom: '1px solid #f8fafc' }}>
                                    <td style={tdStyle}>{fmtDate(p.payment_date)}</td>
                                    <td style={{ ...tdStyle, fontWeight: 600, color: '#16a34a' }}>₹{fmtNum(p.amount)}</td>
                                    <td style={tdStyle}>{p.method || '—'}</td>
                                    <td style={tdStyle}>{p.notes || '—'}</td>
                                    <td style={tdStyle}>
                                        <button onClick={() => deletePayment(p.id)} disabled={saving === 'payment'} style={{ fontSize: 11, color: '#ef4444', background: 'none', border: 'none', cursor: 'pointer' }}>Delete</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
                <AddPaymentForm onAdd={addPayment} saving={saving === 'payment'} />
            </Section>

            {/* Dispatches */}
            <Section title={`Dispatches (${totalDispatched} / ${totalQty} units)`}>
                {order.dispatches?.length > 0 && (
                    <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 13, marginBottom: 8 }}>
                        <thead>
                            <tr style={{ borderBottom: '1px solid #f1f5f9' }}>
                                {['Date', 'Qty', 'Courier', 'Tracking #', 'Track Link', 'Bill', 'Notes'].map(h => <th key={h} style={thStyle}>{h}</th>)}
                            </tr>
                        </thead>
                        <tbody>
                            {order.dispatches.map((d) => (
                                <tr key={d.id} style={{ borderBottom: '1px solid #f8fafc' }}>
                                    <td style={tdStyle}>{fmtDate(d.dispatch_date || d.created_at)}</td>
                                    <td style={{ ...tdStyle, fontWeight: 600 }}>{d.dispatch_qty}</td>
                                    <td style={tdStyle}>{d.courier || '—'}</td>
                                    <td style={{ ...tdStyle, fontFamily: 'monospace', color: '#4f46e5' }}>{d.tracking_number || '—'}</td>
                                    <td style={tdStyle}>
                                        {d.tracking_url
                                            ? <a href={d.tracking_url} target="_blank" rel="noreferrer" style={{ color: '#4f46e5', fontSize: 12 }}>Track →</a>
                                            : '—'}
                                    </td>
                                    <td style={tdStyle}>
                                        {d.bill_path
                                            ? <a href={d.bill_path} target="_blank" rel="noreferrer" style={{ color: '#4f46e5', fontSize: 12 }}>Bill</a>
                                            : '—'}
                                    </td>
                                    <td style={{ ...tdStyle, color: '#64748b', maxWidth: 120 }}>{d.notes || '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
                {remaining > 0 && (
                    <AddDispatchForm items={order.items} remaining={remaining} onAdd={addDispatch} saving={saving === 'dispatch'} />
                )}
                {order.dispatch_status !== 'delivered' && (
                    <div style={{ padding: '8px 16px', borderTop: '1px solid #f1f5f9' }}>
                        <button onClick={markDelivered} disabled={saving === 'delivered'} style={{ ...btnStyle, background: '#16a34a', fontSize: 12 }}>
                            {saving === 'delivered' ? 'Saving…' : '✓ Mark All Delivered'}
                        </button>
                    </div>
                )}
            </Section>
        </div>
    )
}

function AddPaymentForm({ onAdd, saving }) {
    const today = new Date().toISOString().slice(0, 10)
    const [form, setForm] = useState({ amount: '', payment_date: today, received_date: '', method: '', notes: '' })
    const screenshotRef = useRef()
    const set = (k, v) => setForm(f => ({ ...f, [k]: v }))

    function submit(e) {
        e.preventDefault()
        const file = screenshotRef.current?.files?.[0]
        let payload
        if (file) {
            const fd = new FormData()
            fd.append('amount',       parseFloat(form.amount))
            fd.append('payment_date', form.payment_date)
            if (form.received_date) fd.append('received_date', form.received_date)
            if (form.method)        fd.append('method',        form.method)
            if (form.notes)         fd.append('notes',         form.notes)
            fd.append('screenshot', file)
            payload = fd
        } else {
            payload = { ...form, amount: parseFloat(form.amount) }
        }
        onAdd(payload)
        setForm({ amount: '', payment_date: today, received_date: '', method: '', notes: '' })
        if (screenshotRef.current) screenshotRef.current.value = ''
    }

    return (
        <form onSubmit={submit} style={{ padding: '12px 16px', borderTop: '1px solid #f1f5f9', display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'flex-end' }}>
            <FField label="Amount ₹" required><input type="number" step="0.01" min="0.01" required value={form.amount} onChange={e => set('amount', e.target.value)} style={iStyle} /></FField>
            <FField label="Payment Date" required><input type="date" required value={form.payment_date} onChange={e => set('payment_date', e.target.value)} style={iStyle} /></FField>
            <FField label="Received Date"><input type="date" value={form.received_date} onChange={e => set('received_date', e.target.value)} style={iStyle} /></FField>
            <FField label="Method">
                <select value={form.method} onChange={e => set('method', e.target.value)} style={iStyle}>
                    <option value="">—</option>
                    {['UPI','Cash','Bank Transfer','Cheque','NEFT','RTGS','IMPS','DD'].map(m => <option key={m}>{m}</option>)}
                </select>
            </FField>
            <FField label="Note"><input value={form.notes} onChange={e => set('notes', e.target.value)} style={{ ...iStyle, width: 120 }} /></FField>
            <FField label="Screenshot (≤5MB)"><input ref={screenshotRef} type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" style={{ fontSize: 11 }} /></FField>
            <button type="submit" disabled={saving} style={{ ...btnStyle, alignSelf: 'flex-end', marginBottom: 1 }}>{saving ? '…' : '+ Add Payment'}</button>
        </form>
    )
}

function AddDispatchForm({ items, remaining, onAdd, saving }) {
    const today = new Date().toISOString().slice(0, 10)
    const [form, setForm] = useState({ dispatch_qty: remaining, dispatch_date: today, courier: '', tracking_number: '', tracking_url: '', notes: '' })
    const [itemQtys, setItemQtys] = useState({})
    const billRef = useRef()
    const set = (k, v) => setForm(f => ({ ...f, [k]: v }))
    const multiItem = items?.length > 1

    // auto-sum per-item qtys into total dispatch_qty when multi-item
    useEffect(() => {
        if (!multiItem) return
        const total = Object.values(itemQtys).reduce((s, v) => s + (parseInt(v) || 0), 0)
        if (total > 0) setForm(f => ({ ...f, dispatch_qty: total }))
    }, [itemQtys, multiItem])

    function submit(e) {
        e.preventDefault()
        const filtered = Object.fromEntries(Object.entries(itemQtys).filter(([, v]) => parseInt(v) > 0))
        const file = billRef.current?.files?.[0]
        if (file) {
            const fd = new FormData()
            fd.append('dispatch_qty',   parseInt(form.dispatch_qty))
            fd.append('dispatch_date',  form.dispatch_date)
            if (form.courier)          fd.append('courier',          form.courier)
            if (form.tracking_number)  fd.append('tracking_number',  form.tracking_number)
            if (form.tracking_url)     fd.append('tracking_url',     form.tracking_url)
            if (form.notes)            fd.append('notes',            form.notes)
            Object.entries(filtered).forEach(([id, qty]) => fd.append(`item_qtys[${id}]`, qty))
            fd.append('bill', file)
            onAdd(fd)
        } else {
            onAdd({ ...form, dispatch_qty: parseInt(form.dispatch_qty), item_qtys: filtered })
        }
        setForm({ dispatch_qty: remaining, dispatch_date: today, courier: '', tracking_number: '', tracking_url: '', notes: '' })
        setItemQtys({})
        if (billRef.current) billRef.current.value = ''
    }

    return (
        <form onSubmit={submit} style={{ padding: '12px 16px', borderTop: '1px solid #f1f5f9' }}>
            <div style={{ fontSize: 12, fontWeight: 600, color: '#64748b', marginBottom: 10 }}>Record Dispatch</div>

            {items?.length > 1 && (
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', marginBottom: 10 }}>
                    {items.map(item => (
                        <div key={item.id} style={{ background: '#f8fafc', borderRadius: 6, padding: '6px 10px', fontSize: 12 }}>
                            <div style={{ color: '#334155', fontWeight: 500, marginBottom: 4 }}>{item.particulars} (ordered: {item.qty})</div>
                            <input type="number" min="0" max={item.qty} placeholder="qty"
                                value={itemQtys[item.id] || ''}
                                onChange={e => setItemQtys(q => ({ ...q, [item.id]: e.target.value }))}
                                style={{ ...iStyle, width: 60 }}
                            />
                        </div>
                    ))}
                </div>
            )}

            <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'flex-end' }}>
                <FField label="Qty *" required><input type="number" min="1" required value={form.dispatch_qty} onChange={e => set('dispatch_qty', e.target.value)} style={{ ...iStyle, width: 70 }} /></FField>
                <FField label="Date *" required><input type="date" required value={form.dispatch_date} onChange={e => set('dispatch_date', e.target.value)} style={iStyle} /></FField>
                <FField label="Courier">
                    <input list="couriers" value={form.courier} onChange={e => set('courier', e.target.value)} style={{ ...iStyle, width: 130 }} />
                    <datalist id="couriers">
                        {['Delhivery','BlueDart','DTDC','Xpressbees','Ekart','FedEx','DHL','Shadowfax','India Post','Amazon Logistics'].map(c => <option key={c} value={c} />)}
                    </datalist>
                </FField>
                <FField label="Tracking #"><input value={form.tracking_number} onChange={e => set('tracking_number', e.target.value)} style={{ ...iStyle, width: 130 }} /></FField>
                <FField label="Tracking URL"><input type="url" value={form.tracking_url} onChange={e => set('tracking_url', e.target.value)} style={{ ...iStyle, width: 200 }} /></FField>
                <FField label="Notes"><input value={form.notes} onChange={e => set('notes', e.target.value)} style={{ ...iStyle, width: 140 }} /></FField>
                <FField label="Bill / Invoice (≤10MB)"><input ref={billRef} type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" style={{ fontSize: 11 }} /></FField>
                <button type="submit" disabled={saving} style={{ ...btnStyle, alignSelf: 'flex-end', marginBottom: 1 }}>{saving ? '…' : '+ Record Dispatch'}</button>
            </div>
        </form>
    )
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function Section({ title, children }) {
    return (
        <div style={{ background: '#fff', borderRadius: 12, border: '1px solid #e2e8f0', marginBottom: 16, overflow: 'hidden' }}>
            <div style={{ padding: '11px 16px', borderBottom: '1px solid #f1f5f9', fontWeight: 600, fontSize: 13, color: '#0f172a' }}>{title}</div>
            {children}
        </div>
    )
}

function StatTile({ label, value, warn = false, accent = '#4f46e5' }) {
    const col = warn ? (accent === '#4f46e5' ? '#dc2626' : accent) : '#0f172a'
    const borderCol = warn ? (accent === '#4f46e5' ? '#dc2626' : accent) : accent
    return (
        <div style={{
            background: '#fff', borderRadius: 10,
            border: `1px solid ${warn && accent !== '#4f46e5' ? accent + '55' : '#e2e8f0'}`,
            overflow: 'hidden',
        }}>
            <div style={{ height: 3, background: `linear-gradient(90deg, ${borderCol}, ${borderCol}cc)` }} />
            <div style={{ padding: '14px 18px' }}>
                <div style={{ fontSize: 10, color: '#94a3b8', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '0.06em', marginBottom: 6 }}>{label}</div>
                <div style={{ fontSize: 20, fontWeight: 700, color: col }}>{value}</div>
            </div>
        </div>
    )
}

function InfoRow({ label, value, highlight }) {
    return (
        <div style={{ padding: '8px 16px', borderBottom: '1px solid #f8fafc', background: highlight ? '#fffbeb' : 'transparent' }}>
            <span style={{ fontSize: 11, color: '#94a3b8', fontWeight: 600, textTransform: 'uppercase', marginRight: 8 }}>{label}:</span>
            <span style={{ fontSize: 13, color: '#334155' }}>{value}</span>
        </div>
    )
}

function FField({ label, children, required }) {
    return (
        <div>
            <div style={{ fontSize: 10, fontWeight: 600, color: '#94a3b8', textTransform: 'uppercase', marginBottom: 4 }}>{label}{required && ' *'}</div>
            {children}
        </div>
    )
}

function PgBtn({ children, onClick, disabled }) {
    return <button onClick={onClick} disabled={disabled} style={{ padding: '6px 14px', fontSize: 12, borderRadius: 6, border: '1px solid #e2e8f0', background: disabled ? '#f8fafc' : '#fff', color: disabled ? '#cbd5e1' : '#334155', cursor: disabled ? 'default' : 'pointer' }}>{children}</button>
}

function validationMsg(err) {
    const errs = err?.response?.data?.errors
    return errs ? Object.values(errs).flat().join('\n') : (err?.response?.data?.message || 'Failed.')
}

const btnStyle = { padding: '8px 16px', fontSize: 13, fontWeight: 600, borderRadius: 8, border: 'none', background: '#4f46e5', color: '#fff', cursor: 'pointer' }
const iStyle   = { padding: '7px 10px', fontSize: 13, border: '1px solid #e2e8f0', borderRadius: 7, outline: 'none', background: '#fff', color: '#334155' }
const thStyle  = { padding: '8px 14px', textAlign: 'left', color: '#94a3b8', fontWeight: 600, fontSize: 11, textTransform: 'uppercase', letterSpacing: '0.04em' }
const tdStyle  = { padding: '10px 14px', color: '#334155' }
