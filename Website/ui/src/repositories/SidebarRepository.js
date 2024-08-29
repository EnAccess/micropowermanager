import Client from '@/repositories/Client/AxiosClient'
import { baseUrl } from '@/repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/sidebar`

export default {
    list() {
        return Client.get(`${resource}`)
    },
}
