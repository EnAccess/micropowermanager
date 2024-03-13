import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/revenue`

export default {
    getRevenueForPeriod(targetPeriod) {
        return Client.post(`${resource}`, targetPeriod)
    },
}
