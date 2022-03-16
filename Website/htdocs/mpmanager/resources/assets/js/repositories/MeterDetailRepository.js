import Client from './Client/AxiosClient'
const resource = '/api/meters'


export default {
    detail(meterSerial){
        return  Client.get(`${resource}/${meterSerial}`)
    },
    revenue(meterSerial){
        return Client.get(`${resource}/${meterSerial}/revenue`)
    }
}
