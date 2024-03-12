import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class UsageTypeListService {
    constructor() {
        this.repository = Repository.get('usageType')
        this.usageTypeList = []
    }

    async list() {
        try {
            let response = await this.repository.list()
            if (response.status === 200) {
                this.usageTypeList = response.data.data
                return this.usageTypeList
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let erorMessage = e.response.data.message
            return new ErrorHandler(erorMessage, 'http')
        }
    }
}
