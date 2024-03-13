import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/paymenthistories`

export default {
    getPaymentDetail(personId, period) {
        return Client.get(`${resource}/${personId}/payments/${period}`)
    },
    getFlow(personId) {
        return Client.get(`${resource}/${personId}/flow`)
    },
    getPeriod(personId) {
        return Client.get(`${resource}/${personId}/period`)
    },
    getDebt(personId) {
        return Client.get(`${resource}/debt/${personId}`)
    },
}
