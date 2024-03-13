import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'
import { convertObjectKeysToSnakeCase } from '@/Helpers/Utils'

export class DeviceAddressService {
    constructor() {
        this.repository = new Repository.get('deviceAddress')
    }
    async updateDeviceAddresses(devices) {
        try {
            const params = devices.map((device) =>
                convertObjectKeysToSnakeCase(device),
            )
            const { data, status, error } = await this.repository.update(params)
            if (status !== 200) return new ErrorHandler(error, 'http', status)

            return data.data
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }
    }
}
