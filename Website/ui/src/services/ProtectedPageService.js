import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class ProtectedPageService {
    constructor () {
        this.repository = Repository.get('protectedPage')
    }

    async getProtectedPages () {
        try {

            let response = await this.repository.list()
            if (response.status === 200 || response.status === 201) {
                return response.data.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }
        } catch (e) {
            let erorMessage = e.response.data.data.message
            return new ErrorHandler(erorMessage, 'http')
        }
    }
}