import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/people`

export default {

    list (userId, page) {
        return Client.get(`${resource}/${userId}/transactions?page=${page}`)

    },
}
