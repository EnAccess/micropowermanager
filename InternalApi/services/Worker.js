import { parentPort, workerData } from 'worker_threads'
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
    useTLS: true,
})
const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const { companyId, miniGridId, efficiencyCurve, socVal } = workerData
const forecastPath = `${__dirname}/../company/${companyId}/miniGrid/${miniGridId}/forecast-tool`
const optimizationPath = `${__dirname}/../company/${companyId}/miniGrid/${miniGridId}/optimization_model`

///Inputs_Miner Sheet
const HEADER_CONSUMPTION = 'H'
const HEADER_HASH_RATE = 'I'
const HEADER_REVENUE = 'L'
const SLOPE_CELL = 'D5'
const INTERCEPT_CELL = 'D8'
const INTERCEPT_COEFFICIENT_CELL = 'D11'
const MAX_MINER_POWER_CELL = 'D14'

///Parameters Sheet
const M_MI_CELL = 'D103'
const B_MI_CELL = 'D106'
const CRYPTO_MAX = 'D100'
const P_MI = 'D97'
const SOC_INI = 'D67'

const OUTPUT_SHEET = 'Mini-grid results'
const OUTPUT_CELL = 'O2'

const INPUTS_MINER_SHEET = 'INPUTS_Miner'
const PARAMETERS_SHEET = 'Parameters'

const doBackgroundJobs = async () => {
    console.log('Worker started')
    console.log('-------------------------------')

    if (efficiencyCurve) {
        try {
            await updateEfficiencyCurveForForecasting()
            console.log(
                'Efficiency curve updated for forecasting tool template file'
            )
            console.log('-------------------------------')
        } catch (error) {
            throw new Error(
                'Fail at updating efficiency curve file. Error: ' +
                error.message
            )
        }
    }
    try {
        const result = await runForecastTool()
        console.log('Forecasting finished running')
        console.log('-------------------------------')
    } catch (error) {
        throw new Error('Fail at forecast-tool. Error: ' + error.message)
    }

    try {
        await replaceForecastOutPut()
        console.log('Forecast output replaced')
        console.log('-------------------------------')
    } catch (error) {
        throw new Error(
            'Fail at replacing forecast output. Error: ' + error.message
        )
    }

    try {
        const result = await runOptimizationTool()
        console.log('Optimization finished running')
        console.log('-------------------------------')
    } catch (error) {
        throw new Error('Fail at optimization-tool. Error: ' + error.message)
    }

    let consumption = 0
    try {
        const result = await readPowerConsumptionOutput()
        console.log(`Power consumption output: ${result}`)
        console.log('-------------------------------')
        consumption = result
    } catch (error) {
        throw new Error(
            'Fail at reading optimization output. Error: ' + error.message
        )
    }

    try {
        const result = await callPusher(consumption)
        console.log('Pusher called')
        console.log('-------------------------------')
    } catch (error) {
        throw new Error('Fail at calling pusher. Error: ' + error.message)
    }
    parentPort.postMessage('done')
}
const callPusher = async (consumption) => {
    return await pusher.trigger('micro-power-manager', 'forecasting-done', {
        companyId: companyId,
        miniGridId: miniGridId,
        powerConsumptionOutput: consumption,
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
        args: [`-c=${forecastPath}/Config.txt`],
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
    let wb = await workBook.xlsx.readFile(file)
    const ws = wb.getWorksheet(OUTPUT_SHEET)
    return ws.getCell(OUTPUT_CELL).value
}
const updateEfficiencyCurveForForecasting = async () => {

    const file = `${forecastPath}/resources/05_output/raw/excel_sheet.xlsx`
    const workBook = new ExcelJS.Workbook()
    const wb = await workBook.xlsx.readFile(file)
    const ws = wb.getWorksheet(INPUTS_MINER_SHEET)

    setColumnsAsNull(ws, HEADER_CONSUMPTION)
    setColumnsAsNull(ws, HEADER_HASH_RATE)
    setColumnsAsNull(ws, HEADER_REVENUE)
    let powerConsumptions = []
    let hashRates = []
    let profit = 0
    let counter = 1

    for (const key in efficiencyCurve.power_consumption_in_kw) {
        let index = Number(key) + 4

        if (counter === 1) {
            let cRevenue = ws.getCell(`${HEADER_REVENUE}${index}`)
            cRevenue.value = efficiencyCurve.eur_per_th
            profit = efficiencyCurve.eur_per_th
        }

        const hashRate = efficiencyCurve.hashrate_in_th_per_second[key]
        let cHashRate = ws.getCell(`${HEADER_HASH_RATE}${index}`)
        cHashRate.value = hashRate
        console.log(`cHashRate-${index}: `, hashRate)
        hashRates.push(hashRate)

        const powerConsumption = efficiencyCurve.power_consumption_in_kw[key]
        let cConsumption = ws.getCell(`${HEADER_CONSUMPTION}${index}`)
        cConsumption.value = powerConsumption
        console.log(`cConsumption-${index}: `, powerConsumption)
        powerConsumptions.push(powerConsumption)

        counter++
    }

    const slope = calculateSlope(hashRates, powerConsumptions)
    let cSlope = ws.getCell(SLOPE_CELL)
    console.log(`cSlope: `, slope)
    cSlope.value = slope

    const intercept = calculateIntercept(hashRates, powerConsumptions)
    let cIntercept = ws.getCell(INTERCEPT_CELL)
    console.log(`cIntercept: `, intercept)
    cIntercept.value = intercept

    const maximumPowerConsumption = Math.max(...powerConsumptions)
    const interceptCoefficient = intercept / maximumPowerConsumption

    let cInterceptCoefficient = ws.getCell(INTERCEPT_COEFFICIENT_CELL)
    console.log(`cInterceptCoefficient: `, interceptCoefficient)
    cInterceptCoefficient.value = interceptCoefficient

    let cMaxMinerPower = ws.getCell(MAX_MINER_POWER_CELL)
    console.log(`cMaxMinerPower: `, maximumPowerConsumption)
    cMaxMinerPower.value = maximumPowerConsumption

    const ps = wb.getWorksheet(PARAMETERS_SHEET)
    let cM_mi = ps.getCell(M_MI_CELL)
    console.log(`cM_mi: `, slope)
    cM_mi.value = slope

    let cB_mi = ps.getCell(B_MI_CELL)
    cB_mi.value = interceptCoefficient
    console.log(`cB_mi: `, interceptCoefficient)

    let cCrypto_max = ps.getCell(CRYPTO_MAX)
    cCrypto_max.value = maximumPowerConsumption
    console.log(`cCrypto_max: `, maximumPowerConsumption)

    let cP_mi = ps.getCell(P_MI)
    cP_mi.value = profit
    console.log(`cP_mi: `, profit)

    let cSocIni = ps.getCell(SOC_INI)
    cSocIni.value = socVal
    console.log(`cSocIni: `, socVal)

    await workBook.xlsx.writeFile(file)
    console.log('**********************************************')
}
const setColumnsAsNull = (ws, columnHeader) => {
    ws.getColumn(columnHeader).eachCell(function (cell, rowNumber) {
        if (rowNumber > 3 && cell.value != null) {
            cell.value = null
        }
    })
}
const calculateSlope = (x, y) => {
    let n = x.length

    if (n <= 1) {
        // Handle special case where there is only one item in either array
        // You can return a specific value or handle it differently based on your requirements
        return 1; // or throw an error, return 1, etc.
    }
    let sum_x = 0
    let sum_y = 0
    let sum_xy = 0
    let squareSum_x = 0

    for (let i = 0; i < n; i++) {
        sum_x += x[i]
        sum_y += y[i]
        sum_xy += x[i] * y[i]
        squareSum_x += x[i] * x[i]
    }

    return (n * sum_xy - sum_x * sum_y) / (n * squareSum_x - sum_x * sum_x)
}
const calculateIntercept = (x, y) => {
    let n = x.length

    if (n <= 1) {
        // Handle special case where there is only one item in either array
        // You can return a specific value or handle it differently based on your requirements
        return 0; // or throw an error, return 0, etc.
    }
    let sum_x = 0
    let sum_y = 0
    let sum_xy = 0
    let squareSum_x = 0

    for (let i = 0; i < n; i++) {
        sum_x += x[i]
        sum_y += y[i]
        sum_xy += x[i] * y[i]
        squareSum_x += x[i] * x[i]
    }

    let m = (n * sum_xy - sum_x * sum_y) / (n * squareSum_x - sum_x * sum_x)
    return (sum_y - m * sum_x) / n
}

doBackgroundJobs()
