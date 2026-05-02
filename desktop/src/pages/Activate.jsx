export default function Activate({ status, deviceName, onRetry }) {
    const isPending  = status === 'pending'
    const isRejected = status === 'rejected'

    return (
        <div className="min-h-screen bg-slate-900 flex items-center justify-center p-6">
            <div className="bg-white rounded-2xl shadow-2xl p-10 w-full max-w-md text-center">

                {isPending && (
                    <>
                        <div className="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg className="w-8 h-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.75}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h1 className="text-xl font-bold text-slate-900 mb-2">Waiting for Approval</h1>
                        <p className="text-slate-500 text-sm mb-2">
                            Your device <strong className="text-slate-700">{deviceName}</strong> has been registered.
                        </p>
                        <p className="text-slate-500 text-sm mb-8">
                            An admin approval request has been sent. Once approved, you can log in.
                        </p>
                        <button
                            onClick={onRetry}
                            className="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors"
                        >
                            Check Again
                        </button>
                        <p className="mt-4 text-xs text-slate-400">Auto-checks every 30 seconds</p>
                    </>
                )}

                {isRejected && (
                    <>
                        <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg className="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.75}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </div>
                        <h1 className="text-xl font-bold text-slate-900 mb-2">Access Denied</h1>
                        <p className="text-slate-500 text-sm mb-8">
                            This device has been rejected. Contact your admin to get access.
                        </p>
                        <p className="text-xs text-slate-400">Device: {deviceName}</p>
                    </>
                )}
            </div>
        </div>
    )
}
