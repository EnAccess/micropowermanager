import Client from './Client/AxiosClient'
export default {
    get(url,params){
        return Client.get(`${url}`,{params: params})
    },
    post(url,postData){
        return Client.post(`${url}`,postData)
    }
}
