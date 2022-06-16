import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/revenue`

export default {
    trends(miniGridId,period){
        return  Client.post(`${resource}/trends/${miniGridId}`,period)
    },
    tickets(miniGridId){
        return  Client.get(`${resource}/tickets/${miniGridId}`)
    }

}
