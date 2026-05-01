import axios from 'axios'

const BASE_URL = import.meta.env.VITE_API_URL || 'https://YOUR_RENDER_APP.onrender.com/api'

let _token = null
let _fingerprint = null
let _onUnauthorized = null
let _handlingUnauth = false

export function setToken(token)             { _token = token }
export function setFingerprint(fp)          { _fingerprint = fp }
export function clearToken()                { _token = null }
export function onUnauthorized(cb)          { _onUnauthorized = cb }
export function resetUnauthorizedGuard()    { _handlingUnauth = false }

const http = axios.create({ baseURL: BASE_URL })

http.interceptors.request.use(config => {
    if (_token)       config.headers['Authorization'] = `Bearer ${_token}`
    if (_fingerprint) config.headers['X-Device-Fingerprint'] = _fingerprint
    return config
})

http.interceptors.response.use(
    res => res,
    err => {
        const url = err?.config?.url || ''
        const isAuthRoute = url.includes('/auth/login') || url.includes('/auth/logout')
        if (err?.response?.status === 401 && _onUnauthorized && !_handlingUnauth && !isAuthRoute) {
            _handlingUnauth = true
            _onUnauthorized()
        }
        return Promise.reject(err)
    }
)

const api = {
    // device
    checkDevice: (fingerprint) =>
        http.post('/device/check', { fingerprint }).then(r => r.data),

    // auth
    login: (email, password, deviceInfo) =>
        http.post('/auth/login', { email, password, ...deviceInfo }).then(r => r.data),

    logout: () =>
        http.post('/auth/logout').then(r => r.data),

    // dealer
    dashboard: (params = {}) =>
        http.get('/dealer/dashboard', { params }).then(r => r.data),

    orders: (params = {}) =>
        http.get('/dealer/orders', { params }).then(r => r.data),

    orderShow: (id) =>
        http.get(`/dealer/orders/${id}`).then(r => r.data),

    orderCreate: (data) =>
        http.post('/dealer/orders', data).then(r => r.data),

    orderUpdateLabel: (id, label_status) =>
        http.patch(`/dealer/orders/${id}/label`, { label_status }).then(r => r.data),

    clients: () =>
        http.get('/dealer/clients').then(r => r.data),

    clientCreate: (data) =>
        http.post('/dealer/clients', data).then(r => r.data),

    clientUpdate: (id, data) =>
        http.put(`/dealer/clients/${id}`, data).then(r => r.data),

    products: () =>
        http.get('/dealer/products').then(r => r.data),
}

export default api
