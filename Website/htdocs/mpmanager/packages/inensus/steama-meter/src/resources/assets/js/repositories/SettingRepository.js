const resource = '/api/steama-meters/steama-setting'
import Client from './Client/AxiosClient'

export default {
    list() {
        return Client.get(`${resource}`)
    },

}