import express from 'express'
import { ProcessService } from '../services/ProcessService.js'
import { FileService } from '../services/FileService.js'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

export const jetsonRouter = express.Router()

jetsonRouter.post('/configure', async (req, res) => {
    const params = req.body
    const companyId = params.companyId
    const miniGridId = params.miniGridId
    const timeZone = params.timeZone
    const region = params.region

    try {
        await FileService.configureDevice(companyId, miniGridId, timeZone, region)
        res.send({ success: true })
    } catch (error) {
        console.log(error)
        res.status(400).send({
            error: {
                message: error,
            },
        })
    }
})
jetsonRouter.post('/forecast', async (req, res) => {
    const params = req.body
    const companyId = params.companyId
    const miniGridId = params.miniGridId
    const efficiencyCurve = params.efficiencyCurve
    const socVal = params.socVal
    const eccMax = params.consumptionCapacity

    try {
        await ProcessService.forecast(companyId, miniGridId, efficiencyCurve, socVal, eccMax)

        res.send({ success: true })
    } catch (error) {
        console.log(error)
        res.status(400).send({
            error: {
                message: error,
            },
        })
    }
})