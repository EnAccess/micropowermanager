import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/spark-meters/sm-credential`

import Client from './Client/AxiosClient'

export default {
    get(){
        return  Client.get(`${resource}`)
    },
    put(credentials){
        return    Client.put(`${resource}`,credentials)
    },
    check(){
        return  Client.get(`${resource}/check`)
    }
}
