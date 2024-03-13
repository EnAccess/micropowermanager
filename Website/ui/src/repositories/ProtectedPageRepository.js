import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

export const resource = `${baseUrl}/api/protected-pages`

export default {
    list() {
        return Client.get(`${resource}`)
    },
}
