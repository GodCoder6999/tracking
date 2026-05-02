const { contextBridge, ipcRenderer } = require('electron')

contextBridge.exposeInMainWorld('electron', {
    getDeviceInfo:       () => ipcRenderer.invoke('get-device-info'),
    minimizeWindow:      () => ipcRenderer.send('window-minimize'),
    maximizeWindow:      () => ipcRenderer.send('window-maximize'),
    closeWindow:         () => ipcRenderer.send('window-close'),
    isMaximized:         () => ipcRenderer.invoke('is-maximized'),
    onWindowStateChange: (cb) => {
        ipcRenderer.on('window-state-change', (_, state) => cb(state))
        return () => ipcRenderer.removeAllListeners('window-state-change')
    },
})
