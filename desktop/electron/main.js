const { app, BrowserWindow, dialog, ipcMain } = require('electron')
const path = require('path')
const os = require('os')
const crypto = require('crypto')
const { autoUpdater } = require('electron-updater')

const isDev = process.env.NODE_ENV === 'development' || !app.isPackaged

// generate stable hardware fingerprint
function getFingerprint() {
    const data = [
        os.hostname(),
        os.platform(),
        os.arch(),
        os.cpus()[0]?.model || '',
    ].join('|')
    return crypto.createHash('sha256').update(data).digest('hex')
}

function getDeviceInfo() {
    return {
        fingerprint: getFingerprint(),
        device_name: os.hostname(),
        platform: `${os.platform()} ${os.release()}`,
    }
}

function createWindow() {
    const win = new BrowserWindow({
        width: 1280,
        height: 800,
        minWidth: 1024,
        minHeight: 640,
        title: 'Track',
        frame: false,
        titleBarStyle: 'hidden',
        backgroundColor: '#0f172a',
        icon: path.join(__dirname, '../public/icon.ico'),
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            contextIsolation: true,
            nodeIntegration: false,
        },
        show: false,
    })

    win.setMenuBarVisibility(false)

    const sendState = () => win.webContents.send('window-state-change', {
        maximized:  win.isMaximized(),
        fullscreen: win.isFullScreen(),
    })

    win.on('maximize',           sendState)
    win.on('unmaximize',         sendState)
    win.on('enter-full-screen',  sendState)
    win.on('leave-full-screen',  sendState)

    if (isDev) {
        win.loadURL('http://localhost:5173')
        win.webContents.openDevTools()
    } else {
        win.loadFile(path.join(__dirname, '../dist/index.html'))
    }

    win.once('ready-to-show', () => win.show())

    if (!isDev) {
        autoUpdater.checkForUpdatesAndNotify()
    }
}

app.whenReady().then(createWindow)

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit()
})

// IPC — expose device info to renderer
ipcMain.handle('get-device-info', () => getDeviceInfo())
ipcMain.handle('is-maximized', () => {
    const win = BrowserWindow.getFocusedWindow()
    return { maximized: win?.isMaximized() ?? false, fullscreen: win?.isFullScreen() ?? false }
})

// IPC — window controls
ipcMain.on('window-minimize', () => BrowserWindow.getFocusedWindow()?.minimize())
ipcMain.on('window-maximize', () => {
    const win = BrowserWindow.getFocusedWindow()
    win?.isMaximized() ? win.unmaximize() : win?.maximize()
})
ipcMain.on('window-close', () => BrowserWindow.getFocusedWindow()?.close())

// auto-updater events
autoUpdater.on('update-downloaded', () => {
    dialog.showMessageBox({
        type: 'info',
        title: 'Update Ready',
        message: 'A new version has been downloaded. The app will restart to install it.',
        buttons: ['Restart Now'],
    }).then(() => autoUpdater.quitAndInstall(true, true))
})
