import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
export class ManufacturerService {
    constructor () {
        this.repository = Repository.get('manufacturer')
        this.list = []
    }

    async getManufacturers () {
        try {
            const {data, status, error} = await this.repository.list()
            if (status !== 200) return new ErrorHandler(error, 'http', status)
            this.list = data.data

            return this.list
        }catch (e){
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }
}