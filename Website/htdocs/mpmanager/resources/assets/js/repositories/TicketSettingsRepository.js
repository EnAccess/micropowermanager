const resource = '/api/ticket-settings'
import Client from './Client/AxiosClient'

export default {
    list(){
        return Client.get(`${resource}`)
    },
    update(id, ticketSettings){
        return Client.put(`${resource}/${id}`, ticketSettings)
    }
}
