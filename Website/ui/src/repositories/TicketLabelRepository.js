import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/tickets/api/labels`

export default {
    list () {
        return Client.get(`${resource}`)
    },

    create (labelPM) {
        return Client.post(`${resource}`, labelPM)
    },

}
