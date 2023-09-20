import moment from 'moment-timezone'
import ExcelJS from 'exceljs'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const TIME_SERIES_COUNT = 192
const ESOL_SHEET = 'EPV'
const ECON_SHEET = 'loadA'
const CELL = 'B'
const CELL_STARTING = 3
const CELL_ENDING = 194
const SOC_LOWEST_ONE_START_INDEX = 11
const SOC_LOWEST_ONE_END_INDEX = 106
const SOC_LOWEST_TWO_START_INDEX = 11
const SOC_LOWEST_TWO_END_INDEX = 191

/// Esol1 ..  EPV sheet values array
/// Econ1 ..  LoadA sheet values array
/// Eccmax   Maximum miner consumption in site / 4 in kWh
/// InitSOC  Current state of charges as read from the battery inverter used as initial state of charge in the respective calculation cycle (Modbus - log-decode-SI-main)
/// SOClowest1 O11-DF11
/// SOClowest2 O13-GM13

const EffCharge = 0.92 //Efficiency of charging the battery, fix for now as 0.92
const BatCAP = 149 //Battery capacity [kWh] between minimum state of charge and maximum state of charge. For Nsambya, please consider a fixed value of 149 kWh.
const SOCmin = 30 //Minimum state of charge of the battery [kWh]. For Nsambya, this should be 30 kWh.
const OPT = 1 //start with "1" and decrease by "0.025" until SOClowest2 > SOCmin
export const OptimizationService = {

    getCurrentStepValue (array) {
        const tz = 'Africa/Dar_es_Salaam'
        const now = moment.tz(tz)
        const currentTime = now.valueOf() // Current time in milliseconds

        const fifteenMinutes = 15 * 60 * 1000 // 15 minutes in milliseconds
        const step = Math.floor((currentTime % (24 * 60 * 60 * 1000)) / fifteenMinutes)
        const value = array[step]
        console.log(step, value)
        return value
    },

    async getESolAndEConDataFromPredictions (companyId, miniGridId) {
        const predictionsPath = `${__dirname}/../company/${companyId}/miniGrid/${miniGridId}/forecast-tool/resources/05_output`
        const file = `${predictionsPath}/predictions.xlsm`
        const workBook = new ExcelJS.Workbook()
        let wb = await workBook.xlsx.readFile(file)
        const eSolSheet = wb.getWorksheet(ESOL_SHEET)
        let eSolLine = []
        for (let i = CELL_STARTING; i <= CELL_ENDING; i++) {
            eSolLine.push(eSolSheet.getCell(`${CELL}${i}`).value)
        }
        const eConSheet = wb.getWorksheet(ECON_SHEET)
        let eConLine = []
        for (let i = CELL_STARTING; i <= CELL_ENDING; i++) {
            eConLine.push(eConSheet.getCell(`${CELL}${i}`).value)
        }

        return { eSolLine: eSolLine, eConLine: eConLine }
    },

    async doCalculation (companyId, miniGridId, eccMax, initSoc) {
        const timeSeriesLine = new Array(TIME_SERIES_COUNT).fill('step')
        const { eSolLine, eConLine } = this.getESolAndEConDataFromPredictions(companyId, miniGridId)
        let eBatLine = []
        let batSocWithoutEccLine = []
        let socLowestOneArray = []
        let socLowestTwoArray = []

        for (const stepIndex in timeSeriesLine) {
            let eBat = eConLine[stepIndex] - eSolLine[stepIndex]
            eBatLine.push(eBat)
            let batSocWithoutEcc = 0
            if (stepIndex === 0) {
                const initiator = initSoc * BatCAP
                batSocWithoutEcc = (initiator + eBatLine[stepIndex] * EffCharge) > BatCAP ? BatCAP : (initiator + eBatLine[stepIndex] * EffCharge)
                batSocWithoutEccLine.push(batSocWithoutEcc)

            } else {
                batSocWithoutEcc = (batSocWithoutEccLine[stepIndex - 1] + eBatLine[stepIndex] * EffCharge) > BatCAP ? BatCAP : (batSocWithoutEccLine[stepIndex - 1] + eBatLine[stepIndex] * EffCharge)
                batSocWithoutEccLine.push(batSocWithoutEcc)
            }
            if (stepIndex >= SOC_LOWEST_ONE_START_INDEX && stepIndex <= SOC_LOWEST_ONE_END_INDEX) {
                socLowestOneArray.push(batSocWithoutEcc)
            }
        }

        const socLowestOne = Math.min(...socLowestOneArray)
        let batSocWithEccLine = []
        let eccLine = []
        let optFactor = OPT
        for (const stepIndex in batSocWithoutEccLine) {
            const socLowestTwo = Math.min(...socLowestTwoArray)

            if (!Number.POSITIVE_INFINITY(socLowestTwo) && !Number.NEGATIVE_INFINITY(socLowestTwo) && socLowestTwo < SOCmin) {
                optFactor = optFactor - 0.025
            }

            let ecc = 0
            let batSocWithEcc = 0
            if (stepIndex === 0) {
                const initiator = initSoc * BatCAP

                if ((eBatLine[stepIndex] - eccMax) > 0 && initiator > 0.9 * BatCAP) {
                    ecc = eccMax
                } else if ((eBatLine[stepIndex] - eccMax) > 0) {
                    ecc = eccMax * optFactor
                } else if (eBatLine[stepIndex] > 0) {
                    ecc = eBatLine[stepIndex]
                } else {
                    ecc = (socLowestOne - SOCmin) / 60
                }
                eccLine.push(ecc)
                batSocWithEcc = (initiator + eBatLine[stepIndex] * EffCharge - ecc) > BatCAP ? BatCAP : (initiator + eBatLine[stepIndex] * EffCharge - ecc)
                batSocWithEccLine.push(batSocWithEcc)
            } else {

                if ((eBatLine[stepIndex] - eccMax) > 0 && batSocWithEccLine[stepIndex - 1] > 0.9 * BatCAP) {
                    ecc = eccMax
                } else if ((eBatLine[stepIndex] - eccMax) > 0) {
                    ecc = eccMax * optFactor
                } else if (eBatLine[stepIndex] > 0) {
                    ecc = eBatLine[stepIndex]
                } else {
                    ecc = (socLowestOne - SOCmin) / 60
                }
                eccLine.push(ecc)
                batSocWithEcc = (batSocWithEccLine[stepIndex - 1] + eBatLine[stepIndex] * EffCharge - eccLine[stepIndex]) > BatCAP ? BatCAP : (batSocWithEccLine[stepIndex - 1] + eBatLine[stepIndex] * EffCharge - eccLine[stepIndex])
                batSocWithEccLine.push(batSocWithEcc)
            }

            if (stepIndex >= SOC_LOWEST_TWO_START_INDEX && stepIndex <= SOC_LOWEST_TWO_END_INDEX) {
                socLowestTwoArray.push(batSocWithEcc)
            }
        }
        return this.getCurrentStepValue(eccLine)
    }
}
