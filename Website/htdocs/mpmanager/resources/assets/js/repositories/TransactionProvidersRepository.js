const resource = '/api/transaction-providers'
import Client from './Client/AxiosClient'

export default {

    list () {
        return Client.get(`${resource}`)
    },

}