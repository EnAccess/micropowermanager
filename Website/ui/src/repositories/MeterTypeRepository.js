import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/meter-types`

export default {
    index() {
        return Client.get(`${resource}`)
    },
    store(meterType) {
        return Client.post(`${resource}`, meterType)
    },
    update(meterType) {
        return Client.put(`${resource}/${meterType}`)
    },
}
