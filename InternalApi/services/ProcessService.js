import { Worker } from 'worker_threads'
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
export const ProcessService = {

    async forecast (companyId, miniGridId, efficiencyCurve, socVal) {
        try {

            const worker = new Worker(`${__dirname}/Worker.js`, {
                workerData: {
                    companyId: companyId,
                    miniGridId: miniGridId,
                    efficiencyCurve: efficiencyCurve,
                    socVal: socVal
                }
            })
            worker.on('message', (message) => {
                return message
            })
            worker.on('error', (error) => {
                throw error
            })
            worker.on('unhandledRejection', (error) => {
                throw error
            })
            worker.on('exit', (code) => {
                console.log(code)
                if (code !== 0)
                    throw new Error(`stopped with  ${code} exit code`)
            })
        } catch (error) {
            throw error
        }
    }

}