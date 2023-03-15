import { spawn } from 'child_process'
import path from 'path'
import  Pusher  from 'pusher'

const pusher = new Pusher({
    appId: process.env.APP_ID,
    key: process.env.KEY,
    secret: process.env.SECRET,
    cluster: process.env.CLUSTER,
    useTLS: true
});
export const ProcessService = {

    async forecast (companyId, miniGridId) {
        const forecastPath = `../company/${companyId}/miniGrid/${miniGridId}/forecast-tool`
        try {
            return new Promise((resolve, reject) => {
                const pythonProcess = spawn('python3', [
                    path.join(__dirname, `${forecastPath}/main.py`),
                    '-c',
                    `${forecastPath}/Config.txt`
                ])
                pythonProcess.stderr.on('data', (data) => {
                    reject('error')
                })
                pythonProcess.on('close', () => {
                    pusher.trigger("micro-power-manager", "forecast-tool",{
                        'companyId':companyId,
                        'miniGridId':miniGridId
                    });

                })
            })
        } catch (error) {
            throw error
        }
    },
    async optimization (companyId, miniGridId) {
        const optimizationPath = `../company/${companyId}/miniGrid/${miniGridId}/optimization_model`
        try {
            return new Promise((resolve, reject) => {
                const pythonProcess = spawn('python3', [
                    path.join(__dirname, `${optimizationPath}/miners_base.py`),
                ])
                pythonProcess.stderr.on('data', (data) => {
                    reject('error')
                })
                pythonProcess.on('close', () => {
                    pusher.trigger("micro-power-manager", "optimization-tool",{
                        'companyId':companyId,
                        'miniGridId':miniGridId
                    });
                })
            })
        } catch (error) {
            throw error
        }
    }
}