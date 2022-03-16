const resource = '/tickets/api/labels'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },

    create (labelPM) {
        return Client.post(`${resource}`, labelPM)
    },

}
