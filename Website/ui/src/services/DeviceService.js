import Repository from '@/repositories/RepositoryFactory'
import { ErrorHandler } from '@/Helpers/ErrorHander'

export class DeviceService {
    constructor () {
        this.repository = new Repository.get('device')
    }

    async update (Id, device) {
        try {
            const params = {
                id: device.id,
                person_id: device.personId,
                device_id: device.deviceId,
                device_type: device.deviceType,
                device_serial: device.deviceSerial
            }
            console.log(params)
            const { data, status, error} = await this.repository.update(Id, params)

            if (status !== 200) return new ErrorHandler(error, 'http', status)

            return data.data
        } catch (e) {
            let errorMessage = e.response.data.data.message
            return new ErrorHandler(errorMessage, 'http')
        }

    }

}
