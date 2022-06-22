import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/assets/types`

export default {

    list(id){
        return Client.get(`${resource}/people/${id}`)
    },
    create(id,customerId,assetPM){
        return Client.post(`${resource}/${id}/people/${customerId}`,assetPM)
    },
    show(applianceId){
        return Client.get(`${resource}/people/detail/${applianceId}`)
    }
}
