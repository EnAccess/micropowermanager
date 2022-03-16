const resource = '/api/transactions'
import Client from './Client/AxiosClient'

export default {

    list () {
        return Client.get(`${resource}`)

    },
    analytics (period) {
        return Client.get(`${resource}/analytics/${period}`)
    },
    filteredList (term) {
        return Client.post(`${resource}/advanced`, term)
    },
    get (id) {
        return Client.get(`${resource}/${id}`)
    },

}
