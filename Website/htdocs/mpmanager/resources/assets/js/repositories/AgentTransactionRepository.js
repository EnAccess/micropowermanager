import Client from './Client/AxiosClient'
const  resource = 'api/agents/transactions'

export default {

    list(){
        return Client.get(`${resource}`)
    }
}
