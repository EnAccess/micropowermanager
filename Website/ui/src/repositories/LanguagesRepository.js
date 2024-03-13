import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/settings/languages-list`

export default {
    list() {
        return Client.get(`${resource}`)
    },
}
