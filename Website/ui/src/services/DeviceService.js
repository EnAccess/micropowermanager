import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import {
    convertObjectKeysToCamelCase,
    convertObjectKeysToSnakeCase,
} from '@/Helpers/Utils'

export class DeviceService {
    constructor() {
        this.list = []
        this.device = {}
        this.repository = new Repository.get('device')
    }

    async update(Id, device) {
        try {
            const params = convertObjectKeysToSnakeCase(device)
            const { data, status, error } = await this.repository.update(
                Id,
                params,
            )
            if (status !== 200) return new ErrorHandler(error, 'http', status)
            this.device = convertObjectKeysToCamelCase(data.data)

            return this.device
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }

    async getDevices() {
        try {
            const { data, status, error } = await this.repository.list()
            if (status !== 200) return new ErrorHandler(error, 'http', status)
            this.list = data.data.map((device) =>
                convertObjectKeysToCamelCase(device),
            )

            return this.list
        } catch (e) {
            const errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
