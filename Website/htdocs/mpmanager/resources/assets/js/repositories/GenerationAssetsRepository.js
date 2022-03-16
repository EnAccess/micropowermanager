import Client from './Client/AxiosClient'

const resource = '/api/generation-assets'

export  default {
    list(miniGridId, params){
        return Client.get(`${resource}/${miniGridId}/readings`, {params:params})
    },
}
