import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/meters`

export default {
    detail(meterSerial){
        return  Client.get(`${resource}/${meterSerial}`)
    },
    revenue(meterSerial){
        return Client.get(`${resource}/${meterSerial}/revenue`)
    },
    update(meterId, data){
        return Client.put(`${resource}/${meterId}`, data)
    }
}
