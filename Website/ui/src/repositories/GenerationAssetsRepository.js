import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/generation-assets`

export  default {
    list(miniGridId, params){
        return Client.get(`${resource}/${miniGridId}/readings`, {params:params})
    },
}
