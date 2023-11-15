import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import { convertObjectKeysToSnakeCase } from '@/Helpers/Utils'

export class DeviceService {
    constructor () {
        this.repository = new Repository.get('device')
    }

    async update (Id, device) {
        try {
            const params = convertObjectKeysToSnakeCase(device)
            const { data, status, error} = await this.repository.update(Id, params)
            if (status !== 200) return new ErrorHandler(error, 'http', status)

            return data.data
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }

}
