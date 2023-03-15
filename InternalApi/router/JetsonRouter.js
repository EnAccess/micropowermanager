import express from 'express'
import { ProcessService } from '../services/ProcessService.js'
import { FileService } from '../services/FileService.js'

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

    try {
        await ProcessService.forecast(companyId, miniGridId)
        res.send({ success: true })
    } catch (error) {
        res.status(400).send({
            error: {
                message: error,
            },
        })
    }
})
jetsonRouter.post('/optimization', async (req, res) => {
    const params = req.body
    const companyId = params.companyId
    const miniGridId = params.miniGridId

    try {
        await ProcessService.optimization(companyId, miniGridId)
        res.send({ success: true })
    } catch (error) {
        res.status(400).send({
            error: {
                message: error,
            },
        })
    }
})
jetsonRouter.post('/replace-forecast-output', async (req, res) => {
    const params = req.body
    const companyId = params.companyId
    const miniGridId = params.miniGridId
    const source = __dirname + `../company/${companyId}/miniGrid/${miniGridId}/forecast-tool/resources/05_output/predictions.xlsm`
    const destination = __dirname + `../company/${companyId}/miniGrid/${miniGridId}/optimization_model/Data/Data_input_Miners.xlsx`
    try {
        await FileService.replaceFile(source, destination)
        res.send({ success: true })
    } catch (error) {
        res.status(400).send({
            error: {
                message: error,
            },
        })
    }
})