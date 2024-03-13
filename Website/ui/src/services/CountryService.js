import Repository from '../repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export default class CountryService {
    constructor() {
        this.repository = Repository.get('country')
        this.list = []
    }

    async getCountries() {
        try {
            let response = await this.repository.list()
            if (response.status === 200) {
                this.list = response.data.data
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
