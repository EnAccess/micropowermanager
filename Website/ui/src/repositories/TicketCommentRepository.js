import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/tickets/api/tickets/comments`

export default {

    create (commentPm) {

        return Client.post(`${resource}`, commentPm)
    }
}
