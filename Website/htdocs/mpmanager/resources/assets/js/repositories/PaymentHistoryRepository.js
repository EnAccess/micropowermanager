const resource = '/api/paymenthistories'
import Client from './Client/AxiosClient'

export default {

    getPaymentDetail (personId, period) {
        return Client.get(`${resource}/${personId}/payments/${period}`)
    },
    getFlow(personId){
        return Client.get(`${resource}/${personId}/flow`)
    },
    getPeriod(personId){
        return Client.get(`${resource}/${personId}/period`)
    },
    getDebt(personId){
        return Client.get(`${resource}/debt/${personId}`)
    }
}
