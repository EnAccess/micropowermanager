import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class UsageTypeListService {
    constructor() {
        this.repository = Repository.get('usageType')
        this.list = []
    }

    async getUsageTypes () {
        try {
            this.list = []
            let response = await this.repository.list()
            if (response.status === 200 || response.status === 201) {
                this.list  = response.data.data
                return this.list
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }
}
