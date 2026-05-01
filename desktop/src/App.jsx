import { useEffect, useRef, useState } from 'react'
import api, { setToken, setFingerprint, clearToken, onUnauthorized, resetUnauthorizedGuard } from './api'
import Activate  from './pages/Activate'
import Dashboard from './pages/Dashboard'
import Login     from './pages/Login'
import Titlebar  from './components/Titlebar'

const STORE_TOKEN  = 'track_token'
const STORE_DEALER = 'track_dealer'

function loadStored() {
    try {
        return {
            token:  localStorage.getItem(STORE_TOKEN),
            dealer: JSON.parse(localStorage.getItem(STORE_DEALER) || 'null'),
        }
    } catch {
        return { token: null, dealer: null }
    }
}

export default function App() {
    const [screen, setScreen]           = useState('loading') // loading | login | pending | rejected | dashboard
    const [deviceInfo, setDeviceInfo]   = useState(null)
    const [dealer, setDealer]           = useState(null)
    const pollRef                       = useRef(null)

    useEffect(() => {
        onUnauthorized(() => handleLogout())
        init()
        return () => clearInterval(pollRef.current)
    }, [])

    async function init() {
        const info = window.electron
            ? await window.electron.getDeviceInfo()
            : { fingerprint: 'dev-' + Math.random().toString(36).slice(2), device_name: 'Dev Machine', platform: 'windows' }
        setDeviceInfo(info)
        setFingerprint(info.fingerprint)

        // check existing token
        const stored = loadStored()
        if (stored.token && stored.dealer) {
            setToken(stored.token)
            setDealer(stored.dealer)
            setScreen('dashboard')
            return
        }

        // check device registration status
        try {
            const res = await api.checkDevice(info.fingerprint)
            if (res.status === 'approved') {
                setScreen('login')
            } else if (res.status === 'pending') {
                setScreen('pending')
                startPolling(info.fingerprint)
            } else if (res.status === 'rejected') {
                setScreen('rejected')
            } else {
                setScreen('login')
            }
        } catch {
            setScreen('login')
        }
    }

    function startPolling(fingerprint) {
        pollRef.current = setInterval(async () => {
            try {
                const res = await api.checkDevice(fingerprint)
                if (res.status === 'approved') {
                    clearInterval(pollRef.current)
                    setScreen('login')
                } else if (res.status === 'rejected') {
                    clearInterval(pollRef.current)
                    setScreen('rejected')
                }
            } catch {}
        }, 30_000)
    }

    function handleLogin(result) {
        if (result.deviceStatus === 'pending') {
            setScreen('pending')
            startPolling(deviceInfo.fingerprint)
            return
        }
        if (result.deviceStatus === 'rejected') {
            setScreen('rejected')
            return
        }

        setToken(result.token)
        localStorage.setItem(STORE_TOKEN,  result.token)
        localStorage.setItem(STORE_DEALER, JSON.stringify(result.dealer))
        setDealer(result.dealer)
        setScreen('dashboard')
    }

    function handleLogout() {
        api.logout().catch(() => {})
        clearToken()
        resetUnauthorizedGuard()
        localStorage.removeItem(STORE_TOKEN)
        localStorage.removeItem(STORE_DEALER)
        setDealer(null)
        setScreen('login')
    }

    function handleRetry() {
        clearInterval(pollRef.current)
        init()
    }

    const wrap = (children) => (
        <div style={{ display: 'flex', flexDirection: 'column', height: '100vh', overflow: 'hidden', background: '#0f172a' }}>
            <Titlebar />
            <div style={{ flex: 1, overflow: 'auto', minHeight: 0 }}>
                {children}
            </div>
        </div>
    )

    if (screen === 'loading') return wrap(
        <div style={{ height: '100%', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <div style={{ color: '#94a3b8', fontSize: 14 }}>Starting…</div>
        </div>
    )

    if (screen === 'login')     return wrap(<Login    deviceInfo={deviceInfo} onLogin={handleLogin} />)
    if (screen === 'pending')   return wrap(<Activate status="pending"  deviceName={deviceInfo?.device_name} onRetry={handleRetry} />)
    if (screen === 'rejected')  return wrap(<Activate status="rejected" deviceName={deviceInfo?.device_name} onRetry={handleRetry} />)
    if (screen === 'dashboard') return wrap(<Dashboard dealer={dealer} onLogout={handleLogout} />)

    return null
}
