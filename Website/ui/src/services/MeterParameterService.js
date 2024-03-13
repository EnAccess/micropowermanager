import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class MeterParameterService {
    constructor() {
        this.repository = new Repository.get('meterParameter')
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
