import { ErrorHandler } from '@/Helpers/ErrorHander'
import MeterParameterRepository from '@/repositories/MeterParameterRepository'

export class MeterParameterService {
    constructor() {
        this.repository = MeterParameterRepository
    }

    async update(meterId, params) {
        try {
            let response = await this.repository.update(meterId, params)
            if (response.status === 200) {
                return response
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
