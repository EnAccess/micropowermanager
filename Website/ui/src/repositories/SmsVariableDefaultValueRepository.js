import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/sms-variable-default-value`

export default {
    list() {
        return Client.get(`${resource}`)
    },
}
