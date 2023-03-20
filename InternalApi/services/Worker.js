import { workerData, parentPort } from 'worker_threads'
import { spawn } from 'child_process'
import Pusher from 'pusher'
import path from 'path'
import { fileURLToPath } from 'url'
import { PythonShell } from 'python-shell'
import { FileService } from './FileService.js'

const pusher = new Pusher({
    appId: process.env.APP_ID,
    key: process.env.KEY,
    secret: process.env.SECRET,
    cluster: process.env.CLUSTER,
    useTLS: true
})

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const { companyId, miniGridId } = workerData
const forecastPath = `${__dirname}/../company/${companyId}/miniGrid/${miniGridId}/forecast-tool`
const optimizationPath = `${__dirname}/../company/${companyId}/miniGrid/${miniGridId}/optimization_model`
const doBackgroundJobs = async () => {

    try {
        const result = await runForecastTool()
        console.log('Forecasting finished running result: ')
        console.log(result)
        console.log('-------------------------------')
    } catch (error) {
        parentPort.postMessage({ status: 'Fail at forecast-tool' })
    }

    try {
        const result = await replaceForecastOutPut()
        console.log('Forecast output replaced')
        console.log(result)
        console.log('-------------------------------')
    } catch (error) {
        parentPort.postMessage({ status: 'Fail at replacing forecast output.' })
    }

    try {
        const result = await runOptimizationTool()
        console.log('Optimization finished running result: ')
        console.log(result)
        console.log('-------------------------------')
    } catch (error) {
        parentPort.postMessage({ status: 'Fail at optimization-tool' })
    }

    try {
        const result = await callPusher()
        console.log('Pusher called')
        console.log(result)
        console.log('-------------------------------')
    } catch (error) {
        parentPort.postMessage({ status: 'Fail at calling pusher' })
    }
}

const callPusher = async () => {
    return await pusher.trigger('micro-power-manager', 'forecasting-done', {
        'companyId': companyId,
        'miniGridId': miniGridId
    })
}

const replaceForecastOutPut = async () => {
    const source = `${__dirname}/../company/${companyId}/miniGrid/${miniGridId}/forecast-tool/resources/05_output/predictions.xlsm`
    const destination = `${__dirname}/../company/${companyId}/miniGrid/${miniGridId}/optimization_model/Data/Data_input_Miners.xlsx`

    return await FileService.replaceFile(source, destination)
}

const runForecastTool = async () => {
    const options = {
        mode: 'text',
        pythonPath: '/usr/bin/python3',
        pythonOptions: ['-u'], // get print results in real-time
        args: [`-c=${forecastPath}/Config.txt`]
    }

    return await PythonShell.run(`${forecastPath}/main.py`, options)
}
const runOptimizationTool = async () => {
    const options = {
        mode: 'text',
        pythonPath: '/usr/bin/python3',
        pythonOptions: ['-u'], // get print results in real-time
    }

    return await PythonShell.run(`${optimizationPath}/miners_base.py`, options)
}

doBackgroundJobs()