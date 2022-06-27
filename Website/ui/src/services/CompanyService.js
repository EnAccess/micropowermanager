import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class CompanyService {
    constructor () {
        this.repository = Repository.get('company')
    }

    async register (company) {
        try {

            let response = await this.repository.create(company)
            if (response.status === 200 || response.status === 201) {
                return response.data
            } else {
                return new ErrorHandler(response.error, 'http', response.status)
            }

        } catch (e) {
            let erorMessage = e.response.data.data.message
            return new ErrorHandler(erorMessage, 'http')
        }

    }
}