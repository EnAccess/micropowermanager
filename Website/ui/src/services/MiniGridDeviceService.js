import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class MiniGridDeviceService {
    constructor() {
        this.repository = new Repository.get('miniGridDevice')
        this.list = []
    }

    async getMiniGridDevices(miniGridId) {
        try {
            const { data, status, error } =
                await this.repository.list(miniGridId)
            if (status !== 200) return new ErrorHandler(error, 'http', status)
            this.list = data.data

            return data.data
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
