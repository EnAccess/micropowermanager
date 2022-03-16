const resource = '/api/sms-variable-default-value'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },
  
}