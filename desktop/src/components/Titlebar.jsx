import { useEffect, useState } from 'react'

export default function Titlebar({ title = 'Track' }) {
    const [maximized,  setMaximized]  = useState(false)
    const [fullscreen, setFullscreen] = useState(false)
    const [hovered,    setHovered]    = useState(false)

    useEffect(() => {
        if (!window.electron) return

        // get initial state
        window.electron.isMaximized().then(({ maximized: m, fullscreen: f }) => {
            setMaximized(m)
            setFullscreen(f)
        })

        // listen for state changes
        const cleanup = window.electron.onWindowStateChange(({ maximized: m, fullscreen: f }) => {
            setMaximized(m)
            setFullscreen(f)
            if (!f) setHovered(false)
        })

        return cleanup
    }, [])

    // fullscreen: invisible strip at top — slide down on hover
    if (fullscreen) {
        return (
            <div
                style={{
                    position: 'absolute',
                    top: 0,
                    left: 0,
                    right: 0,
                    zIndex: 9999,
                    height: hovered ? 36 : 4,
                    overflow: 'hidden',
                    transition: 'height 0.15s ease',
                    background: '#0f172a',
                    WebkitAppRegion: 'drag',
                }}
                onMouseEnter={() => setHovered(true)}
                onMouseLeave={() => setHovered(false)}
            >
                <TitlebarInner
                    title={title}
                    maximized={maximized}
                    visible={hovered}
                />
            </div>
        )
    }

    return <TitlebarInner title={title} maximized={maximized} visible />
}

function TitlebarInner({ title, maximized, visible }) {
    return (
        <div
            className="flex items-center justify-between px-4 select-none flex-shrink-0"
            style={{
                height: 36,
                background: '#0f172a',
                WebkitAppRegion: 'drag',
                opacity: visible ? 1 : 0,
                transition: 'opacity 0.1s ease',
                borderBottom: '1px solid rgba(255,255,255,0.05)',
            }}
        >
            {/* left: logo + title */}
            <div className="flex items-center gap-2">
                <div
                    className="flex items-center justify-center text-white font-extrabold text-xs"
                    style={{ width: 18, height: 18, background: '#4f46e5', borderRadius: 4 }}
                >
                    T
                </div>
                <span style={{ fontSize: 12, fontWeight: 600, color: '#64748b', letterSpacing: '0.02em' }}>
                    {title}
                </span>
            </div>

            {/* right: window controls */}
            <div className="flex items-center" style={{ WebkitAppRegion: 'no-drag', gap: 0 }}>
                <WinBtn
                    onClick={() => window.electron?.minimizeWindow()}
                    hoverBg="#334155"
                    title="Minimize"
                >
                    {/* minimise line */}
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor">
                        <rect y="4.5" width="10" height="1" rx="0.5"/>
                    </svg>
                </WinBtn>

                <WinBtn
                    onClick={() => window.electron?.maximizeWindow()}
                    hoverBg="#334155"
                    title={maximized ? 'Restore' : 'Maximize'}
                >
                    {maximized ? (
                        /* restore icon — two overlapping squares */
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" strokeWidth="1.2">
                            <rect x="2.5" y="0.6" width="6.9" height="6.9" rx="0.6"/>
                            <path d="M0.6 2.5v6.9h6.9" strokeLinejoin="round"/>
                        </svg>
                    ) : (
                        /* maximize icon — single square */
                        <svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" strokeWidth="1.2">
                            <rect x="0.6" y="0.6" width="8.8" height="8.8" rx="0.6"/>
                        </svg>
                    )}
                </WinBtn>

                <WinBtn
                    onClick={() => window.electron?.closeWindow()}
                    hoverBg="#dc2626"
                    title="Close"
                >
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" strokeWidth="1.4">
                        <path d="M1 1l8 8M9 1L1 9"/>
                    </svg>
                </WinBtn>
            </div>
        </div>
    )
}

function WinBtn({ onClick, hoverBg, children, title }) {
    const [hover, setHover] = useState(false)
    return (
        <button
            onClick={onClick}
            title={title}
            onMouseEnter={() => setHover(true)}
            onMouseLeave={() => setHover(false)}
            style={{
                width: 40,
                height: 36,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                background: hover ? hoverBg : 'transparent',
                border: 'none',
                cursor: 'default',
                color: hover ? '#fff' : '#64748b',
                transition: 'background 0.1s ease, color 0.1s ease',
                WebkitAppRegion: 'no-drag',
            }}
        >
            {children}
        </button>
    )
}
