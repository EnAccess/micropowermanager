import { workerData, parentPort } from 'worker_threads'
import Pusher from 'pusher'
import path from 'path'
import { fileURLToPath } from 'url'
import { PythonShell } from 'python-shell'
import { FileService } from './FileService.js'
import ExcelJS from 'exceljs'

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

const OUTPUT_SHEET = 'Mini-grid results'
const OUTPUT_CELL = 'O2'

const doBackgroundJobs = async () => {

    try {
        const result = await runForecastTool()
        console.log('Forecasting finished running result: ')
        console.log(result)
        console.log('-------------------------------')
    } catch (error) {
        throw new Error('Fail at forecast-tool. Error: ' + error.message)
    }

    try {
        const result = await replaceForecastOutPut()
        console.log('Forecast output replaced')
        console.log(result)
        console.log('-------------------------------')
    } catch (error) {
        throw new Error('Fail at replacing forecast output. Error: ' + error.message)
    }

    try {
        const result = await runOptimizationTool()
        console.log('Optimization finished running result: ')
        console.log(result)
        console.log('-------------------------------')
    } catch (error) {
        throw new Error('Fail at optimization-tool. Error: ' + error.message)
    }

    let consumption = 0
    try {
        const result = await readPowerConsumptionOutput()
        console.log('Power consumption output consumption: ')
        console.log(result)
        console.log('-------------------------------')
        consumption = result
    } catch (error) {
        throw new Error('Fail at reading optimization output. Error: ' + error.message)
    }

    try {
        const result = await callPusher(consumption)
        console.log('Pusher called')
        console.log(result)
        console.log('-------------------------------')
    } catch (error) {

        throw new Error('Fail at calling pusher. Error: ' + error.message)
    }

    parentPort.postMessage('done')
}
const callPusher = async (consumption) => {
    return await pusher.trigger('micro-power-manager', 'forecasting-done', {
        'companyId': companyId,
        'miniGridId': miniGridId,
        'powerConsumptionOutput': consumption
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
const readPowerConsumptionOutput = async () => {
    const file = `${optimizationPath}/Data/Data_output_Miners.xlsx`
    const workBook = new ExcelJS.Workbook()
    let wb = await workBook.xlsx.readFile(optimizationPath)
    const ws = wb.getWorksheet(OUTPUT_SHEET)
    return ws.getCell(OUTPUT_CELL).value
}

doBackgroundJobs()