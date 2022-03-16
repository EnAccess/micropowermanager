const resource = '/api/sms-appliance-remind-rate'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },
    update (smsApplianceRemindRate) {
        return Client.put(`${resource}/${smsApplianceRemindRate.id}`, smsApplianceRemindRate)
    },
    create (smsApplianceRemindRate) {
        return Client.post(`${resource}`, smsApplianceRemindRate)
    }
}