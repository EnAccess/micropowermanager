const resource = '/tickets'
import Client from './Client/AxiosClient'

export default {

    list () {
        return Client.get(`${resource}`)
    },
    detail (id) {
        return Client.get(`${resource}/${id}`)
    }
}
