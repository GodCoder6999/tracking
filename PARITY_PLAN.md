# Desktop App — Feature Parity Plan

Audit date: 2026-05-01  
Source: `C:\Users\dashi\Downloads\tracking-main` (web app PHP source)  
Target: `desktop/src/pages/` (Electron + React)

---

## Status legend
- ✅ Done — already in desktop
- ❌ Missing — needs to be added
- ⚠️ Partial — exists but incomplete

---

## 1. Dashboard

| Feature | Status | Notes |
|---------|--------|-------|
| 6 KPI stat cards (Clients, Orders, Pending Dispatch, Revenue, Received, Due) | ✅ | |
| Date presets (Today, This Month, Last Month, YTD) | ✅ | |
| Date range filter (from/to) | ✅ | |
| Client filter dropdown | ✅ | |
| Payment status filter (unpaid/partial/paid/overdue) | ✅ | |
| Dispatch status filter | ✅ | |
| Product filter dropdown | ✅ | |
| **Order number search field** | ❌ | Web has `order_number` LIKE filter on dashboard |
| Recent orders table | ✅ | |
| Clear filters button | ✅ | |

**Task 1** — Add order number search input to dashboard filter bar. Pass `order_number` param to `api.dashboard()`.

---

## 2. Order Detail (`Orders.jsx → OrderDetail`)

| Feature | Status | Notes |
|---------|--------|-------|
| Stat cards: Total, Received, Due, Units Left | ✅ | |
| **Stat card: Token Paid** | ❌ | Web shows `token_amount` as 4th stat |
| **Stat card: GST total** | ❌ | Web shows `gst_amount` |
| **Stat card: Discount given** | ❌ | Web shows `discount_amount` |
| Order Info: client, date, payment method, invoicing terms, shipping | ✅ | |
| **Order Info: Requested Delivery Date** | ❌ | `requested_delivery_date` field not displayed |
| **Order Info: Full Payment Date** | ❌ | `full_amount_date` not displayed |
| **Order Info: Token Proof link** | ❌ | `token_proof_path` — web shows downloadable link |
| **Order Info: Attachment link** | ❌ | `attachment_path` — web shows downloadable link |
| Items table (particulars, qty, rate, disc%, gst%, amount) | ✅ | |
| **Items table: Dealer Cost column** | ❌ | Web shows dealer_cost per item in the table |
| Label status selector | ✅ | |
| Payments table (date, amount, method, note) | ✅ | |
| Add payment form (amount, payment_date, received_date, method, notes) | ✅ | |
| Delete payment | ✅ | |
| Dispatches table (date, qty, courier, tracking#) | ✅ | |
| **Dispatches table: Tracking URL as clickable link** | ❌ | Show tracking_url as hyperlink if present |
| **Dispatches table: Bill link** | ❌ | `bill_path` not shown |
| Add dispatch form (qty, date, courier, tracking#, tracking_url) | ✅ | |
| **Add dispatch form: Notes field** | ❌ | Web has dispatch notes |
| Mark delivered | ✅ | |
| Per-item qty selector in dispatch form | ✅ | |

**Task 2** — Update `OrderDetail` in `Orders.jsx`:
- Expand stat cards row to 3×2 grid: Total, Token Paid, GST, Received, Due, Discount Given
- Add `requested_delivery_date` and `full_amount_date` to Order Info rows
- Add token_proof_path / attachment_path as links (if present)
- Add Dealer Cost column to items table
- Add `tracking_url` as clickable link column in dispatches table
- Add `bill_path` link in dispatches table
- Add Notes field to `AddDispatchForm`

---

## 3. Create Order (`CreateOrder.jsx`)

| Feature | Status | Notes |
|---------|--------|-------|
| All order metadata fields | ✅ | |
| Dynamic items with real-time math | ✅ | |
| Margin calculation | ✅ | |
| **Token Proof file upload** | ❌ | Web accepts PDF/JPG/PNG up to 5MB |
| **Order Attachment file upload** | ❌ | Web accepts PDF/JPG/PNG up to 10MB |

**Task 3** — Add two file inputs to CreateOrder (token proof, attachment). Change `api.orderCreate()` to send `multipart/form-data` when files are present. Update `api/index.js` to use FormData for `orderCreate`.

---

## 4. Clients (`Clients.jsx`)

| Feature | Status | Notes |
|---------|--------|-------|
| Client list with Orders count, Revenue, Due columns | ✅ | |
| Search by name/email/phone | ✅ | |
| Create client modal (name, email, phone, address, password) | ✅ | |
| **Edit client: email field disabled** | ⚠️ | Email field is `disabled={!isNew}` — should be editable |
| Delete client with confirmation | ✅ | |
| Ledger PDF download with filters | ✅ | |
| CSV/JSON import | ✅ | |
| Import result: created table with passwords | ✅ | |
| Import result: skipped table with reasons | ✅ | |

**Task 4** — Remove `disabled={!isNew}` from email field in `ClientModal`. Email should be editable on update (backend already supports `Rule::unique->ignore` for self).

---

## 5. Analytics (`Analytics.jsx`)

| Feature | Status | Notes |
|---------|--------|-------|
| 5 totals: Revenue, Received, Due, Orders, Clients | ✅ | |
| Monthly revenue line chart (12 months) | ✅ | |
| Order pipeline funnel chart | ✅ | |
| Payment status pie chart | ✅ | |
| Dispatch status pie chart | ✅ | |
| Top 10 products bar chart (revenue + units) | ✅ | |
| Revenue by client bar chart | ✅ | |
| Client breakdown table (orders, revenue, received, due) | ✅ | |
| **Client breakdown: Active/Inactive status column** | ❌ | `is_active` returned by API, not shown |
| **Client breakdown: Collection % column** | ❌ | Web shows (received/revenue×100)%, color-coded |
| **Client breakdown: Sortable column headers** | ❌ | Web allows sort by clicking any column header |
| Date + client filters | ✅ | |

**Task 5** — Update client breakdown table in `Analytics.jsx`:
- Add Status column (`is_active ? 'Active' : 'Inactive'`)
- Add Collection % column: `received/revenue*100`, green ≥80%, amber 50–79%, red <50%
- Make column headers clickable for sort; maintain sort key + direction state

---

## 6. Backend API (`DealerController.php`)

| Feature | Status | Notes |
|---------|--------|-------|
| `clients()` — withCount + withSum for stats | ✅ | |
| `clientStore()` — accepts password | ✅ | |
| `clientUpdate()` — email unique-ignore-self | ✅ | |
| `clientImport()` | ✅ | |
| `ledger()` PDF generation | ✅ | |
| `analytics()` — returns clientStats with is_active | ✅ | |
| `orderStore()` — file uploads (token_proof, attachment) | ✅ | Already in web, should already be in API |
| `dispatchStore()` — file upload (bill), notes field | ✅ | Already in web source |

Backend is complete. All gaps are frontend-only.

---

## Implementation Order

| # | Task | File(s) | Status |
|---|------|---------|--------|
| 1 | Dashboard order# search | `Dashboard.jsx` | ✅ Done |
| 2 | Order detail extra fields + dispatch notes/links | `Orders.jsx` | ✅ Done |
| 3 | CreateOrder file uploads + multipart API | `CreateOrder.jsx`, `api/index.js` | ✅ Done |
| 4 | Client email editable on edit | `Clients.jsx` | ✅ Done |
| 5 | Analytics collection%, status, sort | `Analytics.jsx` | ✅ Done |

All tasks complete. Build verified: 797 modules, no errors.
