import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/transaction-providers`

export default {

    list () {
        return Client.get(`${resource}`)
    },

}